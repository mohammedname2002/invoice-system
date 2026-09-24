<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\CreditNoteService;
use App\Services\PaymentService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsInvoices;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use BuildsInvoices, RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2025-06-20');
        $this->admin = User::factory()->admin()->create();
        $this->customer = Customer::factory()->create(['name' => 'Northwind Supplies']);
        $product = Product::factory()->price('100.00')->create(['vat_rate' => '0.00']);

        // March: 200.00, fully paid in April
        $march = $this->createInvoice($this->customer, [[$product, 2]], ['issue_date' => '2025-03-10', 'due_date' => '2025-04-09']);
        app(PaymentService::class)->record($march, ['amount' => '200.00', 'paid_at' => '2025-04-01', 'method' => 'cash']);

        // May: 300.00, 100.00 credited in June, nothing paid, overdue
        $may = $this->createInvoice($this->customer, [[$product, 3]], ['issue_date' => '2025-05-05', 'due_date' => '2025-06-04']);
        app(CreditNoteService::class)->create($may, [
            'issue_date' => '2025-06-02',
            'items' => [['invoice_item_id' => $may->items()->first()->id, 'quantity' => 1]],
        ]);

        // Another customer, June: 100.00, not yet due
        $this->createInvoice(Customer::factory()->create(), [[$product, 1]], ['issue_date' => '2025-06-15', 'due_date' => '2025-07-15']);
    }

    #[Test]
    public function the_monthly_summary_groups_invoiced_credited_and_collected_amounts(): void
    {
        $summary = app(ReportService::class)->monthlySummary(2025);

        $this->assertSame(20000, $summary['months'][2]['invoiced']->minor);   // March
        $this->assertSame(20000, $summary['months'][3]['collected']->minor);  // April
        $this->assertSame(30000, $summary['months'][4]['invoiced']->minor);   // May
        $this->assertSame(10000, $summary['months'][5]['credited']->minor);   // June
        $this->assertSame(60000, $summary['totals']['invoiced']->minor);
        $this->assertSame(10000, $summary['totals']['credited']->minor);
        $this->assertSame(20000, $summary['totals']['collected']->minor);
    }

    #[Test]
    public function the_dashboard_and_outstanding_report_show_open_balances(): void
    {
        $stats = app(ReportService::class)->dashboard();
        $this->assertSame(30000, $stats['outstanding']->minor);
        $this->assertSame(20000, $stats['overdue']->minor);
        $this->assertSame(1, $stats['overdue_count']);
        $this->assertSame(10000, $stats['invoiced_this_month']->minor);

        $outstanding = app(ReportService::class)->outstandingByCustomer();
        $this->assertSame('Northwind Supplies', $outstanding->first()['customer']->name);
        $this->assertSame(20000, $outstanding->first()['overdue']->minor);

        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('300.00');
        $this->actingAs($this->admin)->get(route('reports.index'))->assertOk()->assertSee('Northwind Supplies')->assertSee('600.00');
    }

    #[Test]
    public function a_customer_statement_can_be_viewed_filtered_and_downloaded(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.statement', ['customer_id' => $this->customer->id]))
            ->assertOk()
            ->assertSee('INV-2025-000001')
            ->assertSee('INV-2025-000002')
            ->assertSee('200.00'); // balance: 500 - 100 credited - 200 paid

        $this->actingAs($this->admin)
            ->get(route('reports.statement', ['customer_id' => $this->customer->id, 'month' => 5, 'year' => 2025]))
            ->assertOk()
            ->assertDontSee('INV-2025-000001')
            ->assertSee('INV-2025-000002');

        $pdf = $this->actingAs($this->admin)->get(route('reports.statement.pdf', ['customer_id' => $this->customer->id]));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $pdf->assertDownload('statement-northwind-supplies.pdf');
    }

    #[Test]
    public function the_statement_requires_a_valid_customer(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.statement', ['month' => 13]))
            ->assertSessionHasErrors(['customer_id', 'month']);
    }
}
