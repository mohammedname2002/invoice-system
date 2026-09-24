<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Support\LineTotals;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Create an invoice with its lines. The invoice, its items, the stock
     * movements and the totals are written in a single transaction.
     *
     * @param  array{customer_id: int, issue_date: string, due_date?: ?string, notes?: ?string, items: list<array<string, mixed>>}  $data
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $customer = Customer::findOrFail($data['customer_id']);

            $invoice = new Invoice;
            $this->fillHeader($invoice, $customer, $data);
            $invoice->number = $this->numbers->next(
                Invoice::class,
                config('invoicing.number_prefixes.invoice'),
                $invoice->issue_date,
            );
            $invoice->status = InvoiceStatus::Unpaid;
            $invoice->save();

            $this->writeItems($invoice, $customer, $data['items']);
            $this->inventory->recordSale($invoice);
            $this->refreshBalance($invoice);

            return $invoice;
        });
    }

    /**
     * Replace the header and lines of an invoice that has no money applied yet.
     *
     * @param  array{customer_id: int, issue_date: string, due_date?: ?string, notes?: ?string, items: list<array<string, mixed>>}  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data): Invoice {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->ensureNotLocked($invoice, 'edited');

            $customer = Customer::findOrFail($data['customer_id']);

            $this->inventory->reverseSale($invoice);
            $invoice->items()->delete();

            $this->fillHeader($invoice, $customer, $data);
            $invoice->save();

            $this->writeItems($invoice, $customer, $data['items']);
            $this->inventory->recordSale($invoice);
            $this->refreshBalance($invoice);

            return $invoice;
        });
    }

    public function delete(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->ensureNotLocked($invoice, 'deleted');

            $this->inventory->reverseSale($invoice);
            $invoice->delete();
        });
    }

    /**
     * Re-sum payments and credit notes from the database and derive the status.
     * Always recalculates from source rows, so a cached value can never drift.
     */
    public function refreshBalance(Invoice $invoice, ?CarbonInterface $today = null): Invoice
    {
        $invoice->amount_paid = Money::ofMinor((int) $invoice->payments()->sum('amount'));
        $invoice->amount_credited = Money::ofMinor((int) $invoice->creditNotes()->sum('total'));

        $invoice->status = InvoiceStatus::resolve(
            total: $invoice->total,
            settled: $invoice->amount_paid->add($invoice->amount_credited),
            dueDate: $invoice->due_date,
            today: $today ?? Carbon::today(),
        );

        $invoice->save();

        return $invoice;
    }

    /**
     * Move open invoices whose due date has passed to "overdue".
     * Run daily by the scheduler (see routes/console.php).
     */
    public function flagOverdue(?CarbonInterface $today = null): int
    {
        $today ??= Carbon::today();
        $flagged = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::PartiallyPaid])
            ->whereDate('due_date', '<', $today)
            ->lazyById()
            ->each(function (Invoice $invoice) use ($today, &$flagged): void {
                if ($this->refreshBalance($invoice, $today)->status === InvoiceStatus::Overdue) {
                    $flagged++;
                }
            });

        return $flagged;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillHeader(Invoice $invoice, Customer $customer, array $data): void
    {
        $issueDate = Carbon::parse($data['issue_date']);

        $invoice->customer()->associate($customer);
        $invoice->issue_date = $issueDate;
        $invoice->due_date = empty($data['due_date'])
            ? $issueDate->copy()->addDays(config('invoicing.payment_terms_days'))
            : Carbon::parse($data['due_date']);
        $invoice->notes = $data['notes'] ?? null;
        // Snapshot, so later changes to the customer's terms do not rewrite history.
        $invoice->discount_rate = $customer->discount_rate;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeItems(Invoice $invoice, Customer $customer, array $rows): void
    {
        $products = Product::whereIn('id', collect($rows)->pluck('product_id')->filter())->get()->keyBy('id');
        $lines = new Collection;

        foreach (array_values($rows) as $position => $row) {
            $product = isset($row['product_id']) ? $products->get($row['product_id']) : null;

            $unitPrice = Money::of($row['unit_price']);
            $quantity = (int) $row['quantity'];
            // Free-text lines (delivery, services) never get the trade discount.
            $discountRate = $product ? $product->discountRateFor($customer) : '0.00';
            $vatRate = (string) $row['vat_rate'];

            $line = LineTotals::calculate($unitPrice, $quantity, $discountRate, $vatRate);
            $lines->push($line);

            $invoice->items()->create([
                'product_id' => $product?->id,
                'position' => $position,
                'description' => filled($row['description'] ?? null) ? $row['description'] : $product?->name,
                'quantity' => $quantity,
                'free_quantity' => (int) ($row['free_quantity'] ?? 0),
                'unit_price' => $unitPrice,
                'discount_rate' => $discountRate,
                'vat_rate' => $vatRate,
                'subtotal' => $line->subtotal,
                'discount_amount' => $line->discount,
                'tax_amount' => $line->tax,
                'total' => $line->total,
            ]);
        }

        $totals = LineTotals::sum($lines);

        $invoice->subtotal = $totals->subtotal;
        $invoice->discount_total = $totals->discount;
        $invoice->tax_total = $totals->tax;
        $invoice->total = $totals->total;
    }

    private function ensureNotLocked(Invoice $invoice, string $action): void
    {
        if ($invoice->isLocked()) {
            throw ValidationException::withMessages([
                'invoice' => "Invoice {$invoice->number} already has payments or credit notes and can no longer be {$action}. Issue a credit note instead.",
            ]);
        }
    }
}
