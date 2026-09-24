<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Fictional demo data: a small wholesaler of personal care products.
 *
 * Everything goes through the real services, so totals, stock movements,
 * balances and statuses are exactly what the application would produce.
 */
class DemoDataSeeder extends Seeder
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly CreditNoteService $creditNotes,
        private readonly PaymentService $payments,
    ) {}

    public function run(): void
    {
        mt_srand(2025);

        $customers = collect([
            ['Northwind Supplies', '7.50'],
            ['Contoso Retail Group', '5.00'],
            ['Fabrikam Clinics', '10.00'],
            ['Blue Harbor Spa', '0.00'],
            ['Silverline Salons', '2.50'],
            ['Greenleaf Pharmacy', '5.00'],
            ['Cedar & Pine Wellness', '0.00'],
            ['Harborview Hotels', '12.00'],
        ])->map(fn (array $row, int $i) => Customer::create([
            'name' => $row[0],
            'email' => 'accounts@'.str($row[0])->before(' ')->lower()->slug().'.example.com',
            'phone' => sprintf('+1 555 01%02d %04d', $i + 10, 1000 + $i * 37),
            'tax_number' => sprintf('1000%08d003', 12345 + $i * 911),
            'address' => sprintf('%d Market Street, Suite %d, Springfield', 100 + $i * 12, 200 + $i),
            'discount_rate' => $row[1],
        ]));

        $products = collect([
            ['Hand Sanitizer 500 ml', 'HS-500', '6.50', true],
            ['Nitrile Gloves (box of 100)', 'NG-100', '9.90', true],
            ['Face Masks (box of 50)', 'FM-050', '7.25', true],
            ['Hydrating Cleanser 200 ml', 'HC-200', '14.00', true],
            ['Sunscreen SPF 50, 100 ml', 'SS-050', '18.50', true],
            ['Vitamin C Serum 30 ml', 'VC-030', '24.00', true],
            ['Body Lotion 400 ml', 'BL-400', '11.75', true],
            ['Cotton Pads (pack of 80)', 'CP-080', '2.40', true],
            ['Lip Balm 10 g', 'LB-010', '3.10', true],
            ['Night Cream 50 ml', 'NC-050', '27.90', true],
            ['Seasonal Gift Set', 'GS-001', '45.00', false],
        ])->map(function (array $row) {
            $product = new Product([
                'name' => $row[0],
                'sku' => $row[1],
                'vat_rate' => '5.00',
                'apply_customer_discount' => $row[3],
            ]);
            $product->unit_price = Money::of($row[2]);
            $product->stock_quantity = 2500;
            $product->save();

            return $product;
        });

        $recorder = User::where('email', 'admin@example.com')->first();
        $today = Carbon::today();

        // Issue dates oldest first, so invoice numbers run in date order.
        $ages = collect(range(1, 36))->map(fn () => mt_rand(0, 120))->sortDesc()->values();

        foreach ($ages as $daysAgo) {
            $issued = $today->copy()->subDays($daysAgo);
            $customer = $customers->random();

            $items = $products->random(mt_rand(1, 4))->map(fn (Product $product) => [
                'product_id' => $product->id,
                'quantity' => mt_rand(1, 12) * 5,
                'free_quantity' => mt_rand(1, 5) === 1 ? mt_rand(1, 5) : 0,
                'unit_price' => $product->unit_price->toDecimal(),
                'vat_rate' => (string) $product->vat_rate,
            ])->values()->all();

            if (mt_rand(1, 3) === 1) {
                $items[] = [
                    'description' => 'Delivery charge',
                    'quantity' => 1,
                    'unit_price' => '15.00',
                    'vat_rate' => '5.00',
                ];
            }

            $invoice = $this->invoices->create([
                'customer_id' => $customer->id,
                'issue_date' => $issued->toDateString(),
                'due_date' => $issued->copy()->addDays(30)->toDateString(),
                'notes' => mt_rand(1, 4) === 1 ? 'Thank you for your business.' : null,
                'items' => $items,
            ]);

            // Roughly one invoice in seven gets a partial return.
            if (mt_rand(1, 7) === 1) {
                $this->creditPartOf($invoice, $issued, $today);
            }

            $this->settle($invoice->refresh(), $issued, $today, $recorder);
        }

        $this->invoices->flagOverdue($today);
    }

    private function creditPartOf(Invoice $invoice, Carbon $issued, Carbon $today): void
    {
        $item = $invoice->items()->first();

        $this->creditNotes->create($invoice, [
            'issue_date' => $issued->copy()->addDays(mt_rand(1, 10))->min($today)->toDateString(),
            'reason' => 'Damaged in transit',
            'items' => [[
                'invoice_item_id' => $item->id,
                'quantity' => max(1, intdiv($item->quantity, 5)),
                'free_quantity' => 0,
                'line_note' => 'Outer carton crushed',
            ]],
        ]);
    }

    private function settle(Invoice $invoice, Carbon $issued, Carbon $today, ?User $recorder): void
    {
        $age = $issued->diffInDays($today);
        $roll = mt_rand(1, 20);

        // Older invoices are mostly paid; recent ones mostly still open.
        $fraction = match (true) {
            $age > 45 => $roll <= 17 ? 1.0 : ($roll <= 19 ? 0.5 : 0.0),
            $age > 30 => $roll <= 12 ? 1.0 : ($roll <= 16 ? 0.4 : 0.0),
            default => $roll <= 6 ? 1.0 : ($roll <= 12 ? 0.3 : 0.0),
        };

        $balance = $invoice->balance();

        if ($fraction === 0.0 || ! $balance->isPositive()) {
            return;
        }

        $amount = $fraction === 1.0 ? $balance : Money::ofMinor(intdiv($balance->minor * (int) ($fraction * 100), 100));
        $methods = PaymentMethod::cases();

        $this->payments->record($invoice, [
            'amount' => $amount->toDecimal(),
            'paid_at' => $issued->copy()->addDays(mt_rand(3, 40))->min($today)->toDateString(),
            'method' => $methods[array_rand($methods)]->value,
            'reference' => 'TRX-'.mt_rand(100000, 999999),
        ], $recorder);
    }
}
