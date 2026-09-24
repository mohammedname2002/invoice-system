<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsInvoices;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use BuildsInvoices, RefreshDatabase;

    private User $accountant;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2025-06-01');

        $this->accountant = User::factory()->accountant()->create();

        // 10 x 10.00 + 5% VAT = 105.00, due 2025-06-30
        $this->invoice = $this->createInvoice(
            Customer::factory()->create(),
            [[Product::factory()->price('10.00')->create(['vat_rate' => '5.00']), 10]],
            ['issue_date' => '2025-06-01', 'due_date' => '2025-06-30'],
        );
    }

    private function pay(string $amount, array $overrides = [])
    {
        return $this->actingAs($this->accountant)->post(route('payments.store', $this->invoice), array_merge([
            'amount' => $amount,
            'paid_at' => '2025-06-01',
            'method' => 'bank_transfer',
            'reference' => 'TRX-1',
        ], $overrides));
    }

    #[Test]
    public function a_new_invoice_is_unpaid(): void
    {
        $this->assertSame(10500, $this->invoice->total->minor);
        $this->assertSame(InvoiceStatus::Unpaid, $this->invoice->status);
    }

    #[Test]
    public function a_partial_payment_marks_the_invoice_partially_paid(): void
    {
        $this->pay('40.00')->assertRedirect(route('invoices.show', $this->invoice))->assertSessionHas('status');

        $invoice = $this->invoice->fresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame(4000, $invoice->amount_paid->minor);
        $this->assertSame(6500, $invoice->balance()->minor);

        $payment = Payment::sole();
        $this->assertSame($this->accountant->id, $payment->recorded_by);
        $this->assertSame('bank_transfer', $payment->method->value);
    }

    #[Test]
    public function paying_the_remaining_balance_marks_the_invoice_paid(): void
    {
        $this->pay('40.00');
        $this->pay('65.00');

        $invoice = $this->invoice->fresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertTrue($invoice->balance()->isZero());
    }

    #[Test]
    public function a_payment_cannot_exceed_the_outstanding_balance(): void
    {
        $this->pay('100.00');

        $this->pay('5.01')->assertSessionHasErrors('amount');

        $this->assertSame(1, Payment::count());
        $this->assertSame(10000, $this->invoice->fresh()->amount_paid->minor);
    }

    #[Test]
    public function payment_input_is_validated(): void
    {
        $this->pay('0')->assertSessionHasErrors('amount');
        $this->pay('10.555')->assertSessionHasErrors('amount');
        $this->pay('10', ['method' => 'bitcoin'])->assertSessionHasErrors('method');
        $this->pay('10', ['paid_at' => '2025-06-02'])->assertSessionHasErrors('paid_at');

        $this->assertDatabaseCount('payments', 0);
    }

    #[Test]
    public function an_unpaid_invoice_becomes_overdue_after_its_due_date(): void
    {
        $this->travelTo('2025-07-01');

        $this->artisan('invoices:flag-overdue')
            ->expectsOutput('1 invoice(s) flagged as overdue.')
            ->assertSuccessful();

        $this->assertSame(InvoiceStatus::Overdue, $this->invoice->fresh()->status);
    }

    #[Test]
    public function a_partially_paid_invoice_becomes_overdue_and_paying_it_off_clears_that(): void
    {
        $this->pay('50.00');

        $this->travelTo('2025-07-15');
        $this->artisan('invoices:flag-overdue')->assertSuccessful();
        $this->assertSame(InvoiceStatus::Overdue, $this->invoice->fresh()->status);

        $this->pay('55.00', ['paid_at' => '2025-07-15']);
        $this->assertSame(InvoiceStatus::Paid, $this->invoice->fresh()->status);
    }

    #[Test]
    public function the_overdue_job_leaves_paid_and_not_yet_due_invoices_alone(): void
    {
        $this->pay('105.00');
        $open = $this->createInvoice(Customer::factory()->create(), [[Product::factory()->create(), 1]], ['issue_date' => '2025-06-01', 'due_date' => '2025-08-01']);

        $this->travelTo('2025-07-01');
        $this->artisan('invoices:flag-overdue')->expectsOutput('0 invoice(s) flagged as overdue.');

        $this->assertSame(InvoiceStatus::Paid, $this->invoice->fresh()->status);
        $this->assertSame(InvoiceStatus::Unpaid, $open->fresh()->status);
    }

    #[Test]
    public function removing_a_payment_reopens_the_invoice(): void
    {
        $this->pay('105.00');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('payments.destroy', Payment::sole()))->assertRedirect(route('invoices.show', $this->invoice));

        $invoice = $this->invoice->fresh();
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertSame(10500, $invoice->balance()->minor);
    }
}
