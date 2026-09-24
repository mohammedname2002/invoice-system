<?php

namespace Tests\Feature;

use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\CreditNoteService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsInvoices;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use BuildsInvoices, RefreshDatabase;

    private Customer $customer;

    private Product $product;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create();
        $this->invoice = $this->createInvoice($this->customer, [[$this->product, 2]]);
    }

    private function invoicePayload(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->toDateString(),
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => '10.00', 'vat_rate' => '5.00']],
        ];
    }

    #[Test]
    public function guests_are_redirected_to_the_login_page(): void
    {
        foreach ([
            route('dashboard'),
            route('invoices.index'),
            route('invoices.show', $this->invoice),
            route('invoices.pdf', $this->invoice),
            route('customers.index'),
            route('products.index'),
            route('credit-notes.index'),
            route('reports.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->post(route('invoices.store'), $this->invoicePayload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('invoices', 1);
    }

    #[Test]
    public function viewers_can_read_but_not_change_anything(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->get(route('invoices.index'))->assertOk();
        $this->actingAs($viewer)->get(route('invoices.show', $this->invoice))->assertOk()->assertDontSee('Record a payment');
        $this->actingAs($viewer)->get(route('invoices.pdf', $this->invoice))->assertOk();
        $this->actingAs($viewer)->get(route('customers.show', $this->customer))->assertOk();

        $this->actingAs($viewer)->get(route('invoices.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('invoices.store'), $this->invoicePayload())->assertForbidden();
        $this->actingAs($viewer)->put(route('invoices.update', $this->invoice), $this->invoicePayload())->assertForbidden();
        $this->actingAs($viewer)->delete(route('invoices.destroy', $this->invoice))->assertForbidden();
        $this->actingAs($viewer)->post(route('payments.store', $this->invoice), ['amount' => '1', 'paid_at' => now()->toDateString(), 'method' => 'cash'])->assertForbidden();
        $this->actingAs($viewer)->post(route('customers.store'), ['name' => 'Nope Ltd', 'discount_rate' => 0])->assertForbidden();
        $this->actingAs($viewer)->put(route('products.update', $this->product), ['name' => 'Renamed', 'unit_price' => '1', 'vat_rate' => '0'])->assertForbidden();
        $this->actingAs($viewer)->get(route('credit-notes.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('reports.index'))->assertForbidden();

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseMissing('customers', ['name' => 'Nope Ltd']);
    }

    #[Test]
    public function accountants_can_create_and_edit_but_not_delete(): void
    {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)->post(route('invoices.store'), $this->invoicePayload())->assertRedirect();
        $this->assertDatabaseCount('invoices', 2);

        $this->actingAs($accountant)->get(route('reports.index'))->assertOk();

        $this->actingAs($accountant)->delete(route('invoices.destroy', $this->invoice))->assertForbidden();
        $this->actingAs($accountant)->delete(route('customers.destroy', $this->customer))->assertForbidden();
        $this->actingAs($accountant)->delete(route('products.destroy', $this->product))->assertForbidden();

        $payment = app(PaymentService::class)->record($this->invoice, ['amount' => '1.00', 'paid_at' => now()->toDateString(), 'method' => 'cash']);
        $this->actingAs($accountant)->delete(route('payments.destroy', $payment))->assertForbidden();

        $creditNote = app(CreditNoteService::class)->create($this->invoice, [
            'issue_date' => now()->toDateString(),
            'items' => [['invoice_item_id' => $this->invoice->items()->first()->id, 'quantity' => 1]],
        ]);
        $this->actingAs($accountant)->delete(route('credit-notes.destroy', $creditNote))->assertForbidden();

        $this->assertModelExists($this->invoice);
        $this->assertSame(1, CreditNote::count());
    }

    #[Test]
    public function admins_can_delete(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('products.destroy', $this->product))->assertRedirect(route('products.index'));
        $this->actingAs($admin)->delete(route('invoices.destroy', $this->invoice))->assertRedirect(route('invoices.index'));
        $this->actingAs($admin)->delete(route('customers.destroy', $this->customer))->assertRedirect(route('customers.index'));

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function the_role_cannot_be_changed_through_the_profile_form(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->patch(route('profile.update'), [
            'name' => 'Sneaky',
            'email' => $viewer->email,
            'role' => 'admin',
        ]);

        $this->assertSame('viewer', $viewer->fresh()->role->value);
    }
}
