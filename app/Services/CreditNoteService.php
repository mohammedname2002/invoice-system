<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\LineTotals;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreditNoteService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly InventoryService $inventory,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * Invoice lines with how many paid and free units have already been
     * credited and how many can still be returned.
     *
     * @return Collection<int, InvoiceItem>
     */
    public function creditableItems(Invoice $invoice): Collection
    {
        return $invoice->items()
            ->withSum('creditNoteItems as credited_quantity', 'quantity')
            ->withSum('creditNoteItems as credited_free_quantity', 'free_quantity')
            ->get()
            ->each(function (InvoiceItem $item): void {
                $item->setAttribute('available_quantity', max(0, $item->quantity - (int) $item->credited_quantity));
                $item->setAttribute('available_free_quantity', max(0, $item->free_quantity - (int) $item->credited_free_quantity));
            });
    }

    /**
     * Credit some or all units of an invoice's lines. Returned units go back
     * into stock and the invoice balance and status are recalculated.
     *
     * @param  array{issue_date: string, reason?: ?string, items: list<array{invoice_item_id: int, quantity?: int, free_quantity?: int, line_note?: ?string}>}  $data
     */
    public function create(Invoice $invoice, array $data): CreditNote
    {
        return DB::transaction(function () use ($invoice, $data): CreditNote {
            // Lock the invoice so concurrent credit notes cannot over-credit a line.
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $available = $this->creditableItems($invoice)->keyBy('id');

            $creditNote = new CreditNote([
                'issue_date' => $data['issue_date'],
                'reason' => $data['reason'] ?? null,
            ]);
            $creditNote->invoice()->associate($invoice);
            $creditNote->number = $this->numbers->next(
                CreditNote::class,
                config('invoicing.number_prefixes.credit_note'),
                Carbon::parse($data['issue_date']),
            );
            $creditNote->save();

            $lines = new Collection;

            foreach ($data['items'] as $index => $row) {
                $quantity = (int) ($row['quantity'] ?? 0);
                $free = (int) ($row['free_quantity'] ?? 0);

                if ($quantity === 0 && $free === 0) {
                    continue;
                }

                /** @var InvoiceItem|null $item */
                $item = $available->get((int) $row['invoice_item_id']);

                if ($item === null) {
                    throw ValidationException::withMessages(["items.{$index}.invoice_item_id" => 'This line does not belong to the invoice.']);
                }

                if ($quantity > $item->available_quantity) {
                    throw ValidationException::withMessages(["items.{$index}.quantity" => "Only {$item->available_quantity} paid unit(s) of \"{$item->description}\" can still be credited."]);
                }

                if ($free > $item->available_free_quantity) {
                    throw ValidationException::withMessages(["items.{$index}.free_quantity" => "Only {$item->available_free_quantity} free unit(s) of \"{$item->description}\" can still be returned."]);
                }

                // Credit at the exact price, discount and VAT the customer was invoiced at.
                $line = LineTotals::calculate($item->unit_price, $quantity, $item->discount_rate, $item->vat_rate);
                $lines->push($line);

                $creditNote->items()->create([
                    'invoice_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $quantity,
                    'free_quantity' => $free,
                    'unit_price' => $item->unit_price,
                    'discount_rate' => $item->discount_rate,
                    'vat_rate' => $item->vat_rate,
                    'subtotal' => $line->subtotal,
                    'discount_amount' => $line->discount,
                    'tax_amount' => $line->tax,
                    'total' => $line->total,
                    'line_note' => filled($row['line_note'] ?? null) ? Str::limit($row['line_note'], 500, '') : null,
                ]);
            }

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Credit at least one paid or free unit.']);
            }

            $totals = LineTotals::sum($lines);
            $creditNote->subtotal = $totals->subtotal;
            $creditNote->discount_total = $totals->discount;
            $creditNote->tax_total = $totals->tax;
            $creditNote->total = $totals->total;
            $creditNote->save();

            $this->inventory->recordReturn($creditNote);
            $this->invoices->refreshBalance($invoice);

            return $creditNote;
        });
    }

    /**
     * Only descriptive fields are editable. Changing quantities means deleting
     * the credit note and issuing a new one, which keeps the audit trail honest.
     *
     * @param  array{issue_date: string, reason?: ?string}  $data
     */
    public function update(CreditNote $creditNote, array $data): CreditNote
    {
        $creditNote->update([
            'issue_date' => $data['issue_date'],
            'reason' => $data['reason'] ?? null,
        ]);

        return $creditNote;
    }

    public function delete(CreditNote $creditNote): void
    {
        DB::transaction(function () use ($creditNote): void {
            $invoice = Invoice::lockForUpdate()->findOrFail($creditNote->invoice_id);

            $this->inventory->reverseReturn($creditNote);
            $creditNote->delete();

            $this->invoices->refreshBalance($invoice);
        });
    }
}
