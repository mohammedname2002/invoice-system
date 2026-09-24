<?php

namespace Tests\Feature;

use App\Livewire\CustomerTable;
use App\Livewire\ProductTable;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsInvoices;
use Tests\TestCase;

class CustomerAndProductTest extends TestCase
{
    use BuildsInvoices, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function a_customer_can_be_created_updated_and_viewed(): void
    {
        $this->actingAs($this->admin)->post(route('customers.store'), [
            'name' => 'Acme Retail',
            'email' => 'ap@acme.example.com',
            'tax_number' => '100200300400003',
            'discount_rate' => '7.5',
        ])->assertRedirect();

        $customer = Customer::sole();
        $this->assertSame('7.50', $customer->discount_rate);

        $this->actingAs($this->admin)->put(route('customers.update', $customer), [
            'name' => 'Acme Retail Group',
            'discount_rate' => '5',
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame('Acme Retail Group', $customer->fresh()->name);

        $this->actingAs($this->admin)->get(route('customers.show', $customer))->assertOk()->assertSee('Acme Retail Group');
        $this->actingAs($this->admin)->get(route('customers.edit', $customer))->assertOk();
        $this->actingAs($this->admin)->get(route('customers.create'))->assertOk();
    }

    #[Test]
    public function customer_input_is_validated(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), ['name' => 'A', 'email' => 'not-an-email', 'discount_rate' => '101'])
            ->assertSessionHasErrors(['name', 'email', 'discount_rate']);
    }

    #[Test]
    public function a_customer_with_invoices_cannot_be_deleted(): void
    {
        $customer = Customer::factory()->create();
        $this->createInvoice($customer, [[Product::factory()->create(), 1]]);

        $this->actingAs($this->admin)
            ->delete(route('customers.destroy', $customer))
            ->assertSessionHasErrors('customer');

        $this->assertModelExists($customer);
    }

    #[Test]
    public function changing_a_customer_discount_does_not_rewrite_issued_invoices(): void
    {
        $customer = Customer::factory()->withDiscount('10.00')->create();
        $invoice = $this->createInvoice($customer, [[Product::factory()->price('100.00')->create(['vat_rate' => '0.00']), 1]]);

        $customer->update(['discount_rate' => '50.00']);

        $this->assertSame('10.00', $invoice->fresh()->discount_rate);
        $this->assertSame(9000, $invoice->fresh()->total->minor);
    }

    #[Test]
    public function a_product_can_be_created_with_opening_stock_and_updated(): void
    {
        $this->actingAs($this->admin)->post(route('products.store'), [
            'name' => 'Hand Sanitizer 500 ml',
            'sku' => 'HS-500',
            'unit_price' => '6.50',
            'vat_rate' => '5',
            'apply_customer_discount' => '1',
            'stock_quantity' => 40,
        ])->assertRedirect(route('products.index'));

        $product = Product::sole();
        $this->assertSame(650, $product->unit_price->minor);
        $this->assertSame(40, $product->stock_quantity);
        $this->assertTrue($product->apply_customer_discount);

        // Stock is not editable after creation; it moves through invoices and credit notes.
        $this->actingAs($this->admin)->put(route('products.update', $product), [
            'name' => 'Hand Sanitizer 500 ml',
            'sku' => 'HS-500',
            'unit_price' => '7.00',
            'vat_rate' => '5',
            'stock_quantity' => 9999,
        ])->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame(700, $product->unit_price->minor);
        $this->assertSame(40, $product->stock_quantity);
        $this->assertFalse($product->apply_customer_discount);

        $this->actingAs($this->admin)->get(route('products.edit', $product))->assertOk();
        $this->actingAs($this->admin)->get(route('products.create'))->assertOk();
    }

    #[Test]
    public function product_input_is_validated(): void
    {
        Product::factory()->create(['sku' => 'TAKEN']);

        $this->actingAs($this->admin)
            ->post(route('products.store'), ['name' => '', 'sku' => 'TAKEN', 'unit_price' => 'abc', 'vat_rate' => '-1'])
            ->assertSessionHasErrors(['name', 'sku', 'unit_price', 'vat_rate']);
    }

    #[Test]
    public function the_livewire_tables_search(): void
    {
        Customer::factory()->create(['name' => 'Blue Harbor Spa']);
        Customer::factory()->create(['name' => 'Greenleaf Pharmacy']);
        Product::factory()->create(['name' => 'Lip Balm 10 g', 'sku' => 'LB-010']);
        Product::factory()->create(['name' => 'Night Cream 50 ml', 'sku' => 'NC-050']);

        $this->actingAs($this->admin);

        $this->get(route('customers.index'))->assertOk()->assertSeeLivewire(CustomerTable::class);
        $this->get(route('products.index'))->assertOk()->assertSeeLivewire(ProductTable::class);

        Livewire::test(CustomerTable::class)
            ->set('search', 'Harbor')
            ->assertSee('Blue Harbor Spa')
            ->assertDontSee('Greenleaf Pharmacy');

        Livewire::test(ProductTable::class)
            ->set('search', 'NC-050')
            ->assertSee('Night Cream 50 ml')
            ->assertDontSee('Lip Balm 10 g');
    }
}
