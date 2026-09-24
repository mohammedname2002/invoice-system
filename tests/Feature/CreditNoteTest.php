<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Livewire\CreditNoteTable;
use App\Models\CreditNote;
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

class CreditNoteTest extends TestCase
{
    use BuildsInvoices, RefreshDatabase;

    private User $admin;

    private Product $product;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->product = Product::factory()->price('10.00')->create(['vat_rate' => '5.00', 'stock_quantity' => 50]);

        // 10 paid + 2 free units at 10.00, 5% discount, 5% VAT:
        // 100.00 - 5.00 = 95.00 + 4.75 VAT = 99.75
        $this->invoice = $this->createInvoice(
            Customer::factory()->withDiscount('5.00')->create(),
            [[$this->product, 10, 2]],
        );
    }

    private function creditPayload(int $quantity, int $free = 0): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'issue_date' => now()->toDateString(),
            'reason' => 'Damaged in transit',
            'items' => [[
                'invoice_item_id' => $this->invoice->items()->first()->id,
                'quantity' => $quantity,
                'free_quantity' => $free,
                'line_note' => 'Carton crushed',
            ]],
        ];
    }

    #[Test]
    public function a_credit_note_reduces_the_invoice_balance_and_returns_stock(): void
    {
        $this->assertSame(9975, $this->invoice->total->minor);
        $this->assertSame(38, $this->product->fresh()->stock_quantity);

        $response = $this->actingAs($this->admin)->post(route('credit-notes.store'), $this->creditPayload(3, 1));

        $creditNote = CreditNote::sole();
        $response->assertRedirect(route('credit-notes.show', $creditNote));

        // 3 x 10.00 = 30.00 - 5% = 28.50 + 1.43 VAT (1.425 rounds half up) = 29.93
        $this->assertSame('CN-'.now()->year.'-000001', $creditNote->number);
        $this->assertSame(2993, $creditNote->total->minor);
        $this->assertSame('Carton crushed', $creditNote->items->first()->line_note);

        $invoice = $this->invoice->fresh();
        $this->assertSame(2993, $invoice->amount_credited->minor);
        $this->assertSame(9975 - 2993, $invoice->balance()->minor);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);

        $this->assertSame(42, $this->product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['credit_note_id' => $creditNote->id, 'type' => 'credit_return', 'quantity' => 4]);
    }

    #[Test]
    public function crediting_the_whole_invoice_settles_it(): void
    {
        $this->actingAs($this->admin)->post(route('credit-notes.store'), $this->creditPayload(10, 2));

        $invoice = $this->invoice->fresh();
        $this->assertTrue($invoice->balance()->isZero());
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
    }

    #[Test]
    public function a_payment_plus_a_credit_note_for_the_rest_marks_the_invoice_paid(): void
    {
        // Pay everything except the 3 units credited below: 99.75 - 29.93 = 69.82
        app(PaymentService::class)->record($this->invoice, ['amount' => '69.82', 'paid_at' => now()->toDateString(), 'method' => 'bank_transfer']);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $this->invoice->fresh()->status);

        // Credit the other 3 units: 29.93
        $this->actingAs($this->admin)->post(route('credit-notes.store'), $this->creditPayload(3));

        $invoice = $this->invoice->fresh();
        $this->assertSame(9975 - 6982 - 2993, $invoice->balance()->minor);
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
    }

    #[Test]
    public function units_cannot_be_credited_twice(): void
    {
        $this->actingAs($this->admin)->post(route('credit-notes.store'), $this->creditPayload(8))->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('credit-notes.store'), $this->creditPayload(3))
            ->assertSessionHasErrors('items.0.quantity');

        $this->actingAs($this->admin)
            ->post(route('credit-notes.store'), $this->creditPayload(0, 3))
            ->assertSessionHasErrors('items.0.free_quantity');

        $this->assertSame(1, CreditNote::count());
    }

    #[Test]
    public function a_credit_note_needs_at_least_one_unit_and_valid_lines(): void
    {
        $this->actingAs($this->admin)
            ->post(route('credit-notes.store'), $this->creditPayload(0, 0))
            ->assertSessionHasErrors('items');

        $otherInvoice = $this->createInvoice(Customer::factory()->create(), [[$this->product, 1]]);
        $payload = $this->creditPayload(1);
        $payload['items'][0]['invoice_item_id'] = $otherInvoice->items()->first()->id;

        $this->actingAs($this->admin)
            ->post(route('credit-notes.store'), $payload)
            ->assertSessionHasErrors('items.0.invoice_item_id');

        $this->actingAs($this->admin)
            ->post(route('credit-notes.store'), ['invoice_id' => 999, 'issue_date' => 'not-a-date', 'items' => []])
            ->assertSessionHasErrors(['invoice_id', 'issue_date', 'items']);

        $this->assertDatabaseCount('credit_notes', 0);
    }

    #[Test]
    public function deleting_a_credit_note_restores_the_balance_and_the_stock_level(): void
    {
        $this->actingAs($this->admin)->post(route('credit-notes.store'), $this->creditPayload(3, 1));
        $creditNote = CreditNote::sole();

        $this->actingAs($this->admin)->delete(route('credit-notes.destroy', $creditNote))->assertRedirect(route('credit-notes.index'));

        $invoice = $this->invoice->fresh();
        $this->assertModelMissing($creditNote);
        $this->assertTrue($invoice->amount_credited->isZero());
        $this->assertSame(9975, $invoice->balance()->minor);
        $this->assertSame(38, $this->product->fresh()->stock_quantity);
    }

    #[Test]
    public function only_the_date_and_reason_can_be_edited(): void
    {
        $this->actingAs($this->admin)->post(route('credit-notes.store'), $this->creditPayload(1));
        $creditNote = CreditNote::sole();

        $this->actingAs($this->admin)
            ->put(route('credit-notes.update', $creditNote), ['issue_date' => '2025-01-02', 'reason' => 'Wrong shade', 'total' => '0'])
            ->assertRedirect(route('credit-notes.show', $creditNote));

        $creditNote->refresh();
        $this->assertSame('Wrong shade', $creditNote->reason);
        $this->assertSame('2025-01-02', $creditNote->issue_date->toDateString());
        $this->assertSame(998, $creditNote->total->minor);
    }

    #[Test]
    public function the_credit_note_pages_and_pdf_render(): void
    {
        $this->actingAs($this->admin)->post(route('credit-notes.store'), $this->creditPayload(2));
        $creditNote = CreditNote::sole();

        $this->actingAs($this->admin)->get(route('credit-notes.index'))->assertOk()->assertSeeLivewire(CreditNoteTable::class);
        $this->actingAs($this->admin)->get(route('credit-notes.create'))->assertOk()->assertSee($this->invoice->number);
        $this->actingAs($this->admin)
            ->get(route('credit-notes.create', ['invoice' => $this->invoice->id]))
            ->assertOk()
            ->assertSee('Issue credit note')
            ->assertSee($this->product->name);
        $this->actingAs($this->admin)->get(route('credit-notes.show', $creditNote))->assertOk()->assertSee($creditNote->number);
        $this->actingAs($this->admin)->get(route('credit-notes.edit', $creditNote))->assertOk();

        $pdf = $this->actingAs($this->admin)->get(route('credit-notes.pdf', $creditNote));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin);
        Livewire::test(CreditNoteTable::class)
            ->set('search', $this->invoice->number)
            ->assertSee($creditNote->number)
            ->set('search', 'no-such-thing')
            ->assertDontSee($creditNote->number);
    }
}
