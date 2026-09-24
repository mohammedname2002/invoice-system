<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(): View
    {
        return view('customers.index');
    }

    public function create(): View
    {
        return view('customers.create', ['customer' => new Customer(['discount_rate' => '0.00'])]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = $this->customers->create($request->validated());

        return to_route('customers.show', $customer)->with('status', "Customer {$customer->name} created.");
    }

    public function show(Customer $customer): View
    {
        $openInvoices = $customer->invoices()->open()->get();

        return view('customers.show', [
            'customer' => $customer,
            'invoices' => $customer->invoices()->latest('issue_date')->latest('id')->paginate(10),
            'outstanding' => Money::sum($openInvoices->map->balance()),
            'openCount' => $openInvoices->count(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        return to_route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->customers->delete($customer);

        return to_route('customers.index')->with('status', 'Customer deleted.');
    }
}
