<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer;
    }

    public function delete(Customer $customer): void
    {
        if ($customer->invoices()->exists()) {
            throw ValidationException::withMessages([
                'customer' => "{$customer->name} has invoices and cannot be deleted.",
            ]);
        }

        $customer->delete();
    }
}
