<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Livewire\InvoiceTable;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsInvoices;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use BuildsInvoices, RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Product $serum;

    private Product $giftSet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = Customer::factory()->withDiscount('10.00')->create();
        $this->serum = Product::factory()->price('12.50')->create(['vat_rate' => '5.00', 'stock_quantity' => 100]);
        $this->giftSet = Product::factory()->price('40.00')->withoutCustomerDiscount()->create(['vat_rate' => '5.00', 'stock_quantity' => 10]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'issue_date' => '2025-03-01',
            'due_date' => '2025-03-31',
            'notes' => 'Thanks!',
            'items' => [
                ['product_id' => $this->serum->id, 'quantity' => 4, 'free_quantity' => 1, 'unit_price' => '12.50', 'vat_rate' => '5.00'],
                ['product_id' => $this->giftSet->id, 'quantity' => 1, 'unit_price' => '40.00', 'vat_rate' => '5.00'],
                ['description' => 'Delivery charge', 'quantity' => 1, 'unit_price' => '15.00', 'vat_rate' => '5.00'],
            ],
        ], $overrides);
    }

    #[Test]
    public function it_creates_an_invoice_with_items_and_exact_totals(): void
    {
        $response = $this->actingAs($this->admin)->post(route('invoices.store'), $this->payload());

        $invoice = Invoice::with('items')->sole();
        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame('INV-2025-000001', $invoice->number);
        $this->assertSame('10.00', $invoice->discount_rate);
        $this->assertCount(3, $invoice->items);

        // Serum: 4 x 12.50 = 50.00, -10% = 45.00, VAT 2.25 => 47.25 (the free unit is not charged)
        $serumLine = $invoice->items[0];
        $this->assertSame(5000, $serumLine->subtotal->minor);
        $this->assertSame(500, $serumLine->discount_amount->minor);
        $this->assertSame(225, $serumLine->tax_amount->minor);
        $this->assertSame(4725, $serumLine->total->minor);
        $this->assertSame(1, $serumLine->free_quantity);

        // Gift set opts out of the customer discount: 40.00 + 2.00 VAT
        $this->assertSame('0.00', $invoice->items[1]->discount_rate);
        $this->assertSame(4200, $invoice->items[1]->total->minor);

        // Free-text line takes the description and no discount: 15.00 + 0.75
        $this->assertSame('Delivery charge', $invoice->items[2]->description);
        $this->assertNull($invoice->items[2]->product_id);
        $this->assertSame(1575, $invoice->items[2]->total->minor);

        $this->assertSame(10500, $invoice->subtotal->minor);
        $this->assertSame(500, $invoice->discount_total->minor);
        $this->assertSame(500, $invoice->tax_total->minor);
        $this->assertSame(10500, $invoice->total->minor);
        $this->assertSame(10500, $invoice->balance()->minor);
    }

    #[Test]
    public function creating_an_invoice_takes_paid_and_free_units_out_of_stock(): void
    {
        $this->actingAs($this->admin)->post(route('invoices.store'), $this->payload());

        $this->assertSame(95, $this->serum->fresh()->stock_quantity);
        $this->assertSame(9, $this->giftSet->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $this->serum->id, 'type' => 'sale', 'quantity' => -5]);
    }

    #[Test]
    public function invoice_numbers_are_sequential_per_year(): void
    {
        $this->actingAs($this->admin)->post(route('invoices.store'), $this->payload());
        $this->actingAs($this->admin)->post(route('invoices.store'), $this->payload());
        $this->actingAs($this->admin)->post(route('invoices.store'), $this->payload(['issue_date' => '2026-01-05', 'due_date' => null]));

        $this->assertSame(
            ['INV-2025-000001', 'INV-2025-000002', 'INV-2026-000001'],
            Invoice::orderBy('id')->pluck('number')->all(),
        );
    }

    #[Test]
    public function the_due_date_defaults_to_the_configured_payment_terms(): void
    {
        config(['invoicing.payment_terms_days' => 14]);

        $this->actingAs($this->admin)->post(route('invoices.store'), $this->payload(['due_date' => null]));

        $this->assertSame('2025-03-15', Invoice::sole()->due_date->toDateString());
    }

    #[Test]
    public function an_invoice_past_its_due_date_is_created_as_overdue(): void
    {
        $this->travelTo('2025-05-01');

        $this->actingAs($this->admin)->post(route('invoices.store'), $this->payload());

        $this->assertSame(InvoiceStatus::Overdue, Invoice::sole()->status);
    }

    #[Test]
    public function updating_an_invoice_replaces_its_lines_and_recalculates_totals_and_stock(): void
    {
        $invoice = $this->createInvoice($this->customer, [[$this->serum, 10]]);
        $this->assertSame(90, $this->serum->fresh()->stock_quantity);

        $response = $this->actingAs($this->admin)->put(route('invoices.update', $invoice), $this->payload([
            'items' => [
                ['product_id' => $this->serum->id, 'quantity' => 2, 'unit_price' => '12.50', 'vat_rate' => '5.00'],
            ],
        ]));

        $response->assertRedirect(route('invoices.show', $invoice));
        $invoice->refresh()->load('items');

        $this->assertCount(1, $invoice->items);
        // 25.00 - 10% = 22.50 + 1.13 VAT (1.125 rounds half up)
        $this->assertSame(2363, $invoice->total->minor);
        $this->assertSame(98, $this->serum->fresh()->stock_quantity);
        $this->assertSame('INV-'.now()->year.'-000001', $invoice->number, 'The number never changes on update.');
    }

    #[Test]
    public function an_invoice_with_a_payment_can_no_longer_be_edited_or_deleted(): void
    {
        $invoice = $this->createInvoice($this->customer, [[$this->serum, 2]]);
        app(PaymentService::class)->record($invoice, ['amount' => '5.00', 'paid_at' => now()->toDateString(), 'method' => 'cash']);

        $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $this->payload())
            ->assertSessionHasErrors('invoice');

        $this->actingAs($this->admin)
            ->delete(route('invoices.destroy', $invoice))
            ->assertSessionHasErrors('invoice');

        $this->actingAs($this->admin)
            ->get(route('invoices.edit', $invoice))
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertModelExists($invoice);
        $this->assertCount(1, $invoice->fresh()->items);
    }

    #[Test]
    public function deleting_an_invoice_returns_its_stock(): void
    {
        $invoice = $this->createInvoice($this->customer, [[$this->serum, 10, 2]]);
        $this->assertSame(88, $this->serum->fresh()->stock_quantity);

        $this->actingAs($this->admin)->delete(route('invoices.destroy', $invoice))->assertRedirect(route('invoices.index'));

        $this->assertModelMissing($invoice);
        $this->assertSame(100, $this->serum->fresh()->stock_quantity);
        $this->assertDatabaseCount('invoice_items', 0);
    }

    #[Test]
    public function it_validates_the_invoice_payload(): void
    {
        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->payload(['items' => []]))
            ->assertSessionHasErrors('items');

        $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->payload([
                'customer_id' => 999,
                'due_date' => '2025-02-01',
                'items' => [
                    ['product_id' => $this->serum->id, 'quantity' => 0, 'unit_price' => '-1', 'vat_rate' => '120'],
                    ['description' => '', 'quantity' => 1, 'unit_price' => '1.999', 'vat_rate' => '5'],
                ],
            ]))
            ->assertSessionHasErrors([
                'customer_id',
                'due_date',
                'items.0.quantity',
                'items.0.unit_price',
                'items.0.vat_rate',
                'items.1.description',
                'items.1.unit_price',
            ]);

        $this->assertDatabaseCount('invoices', 0);
    }

    #[Test]
    public function blank_rows_from_the_line_editor_are_ignored(): void
    {
        $payload = $this->payload();
        $payload['items'][] = ['product_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '', 'vat_rate' => '5'];

        $this->actingAs($this->admin)->post(route('invoices.store'), $payload)->assertSessionHasNoErrors();

        $this->assertCount(3, Invoice::sole()->items);
    }

    #[Test]
    public function the_invoice_pages_render(): void
    {
        $invoice = $this->createInvoice($this->customer, [[$this->serum, 3]]);

        $this->actingAs($this->admin)->get(route('invoices.index'))->assertOk()->assertSeeLivewire(InvoiceTable::class);
        $this->actingAs($this->admin)->get(route('invoices.create'))->assertOk()->assertSee($this->serum->name);
        $this->actingAs($this->admin)->get(route('invoices.show', $invoice))->assertOk()->assertSee($invoice->number)->assertSee('Record a payment');
        $this->actingAs($this->admin)->get(route('invoices.edit', $invoice))->assertOk();
    }

    #[Test]
    public function the_invoice_table_filters_by_status_customer_and_search(): void
    {
        $other = Customer::factory()->create(['name' => 'Zeta Traders']);
        $paid = $this->createInvoice($this->customer, [[$this->serum, 1]]);
        app(PaymentService::class)->record($paid, ['amount' => $paid->total->toDecimal(), 'paid_at' => now()->toDateString(), 'method' => 'cash']);
        $open = $this->createInvoice($other, [[$this->serum, 1]]);

        $this->actingAs($this->admin);

        Livewire::test(InvoiceTable::class)
            ->assertSee($paid->number)
            ->assertSee($open->number)
            ->set('status', 'paid')
            ->assertSee($paid->number)
            ->assertDontSee($open->number)
            ->call('clearFilters')
            ->set('customerId', (string) $other->id)
            ->assertSee($open->number)
            ->assertDontSee($paid->number)
            ->call('clearFilters')
            ->set('search', 'Zeta')
            ->assertSee($open->number)
            ->assertDontSee($paid->number);
    }

    #[Test]
    public function the_invoice_pdf_can_be_downloaded(): void
    {
        $invoice = $this->createInvoice($this->customer, [[$this->serum, 3]]);

        $response = $this->actingAs($this->admin)->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload($invoice->number.'.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
