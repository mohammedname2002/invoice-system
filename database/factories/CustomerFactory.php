<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('+1 555 01## ####'),
            'tax_number' => fake()->numerify('1000########003'),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'discount_rate' => '0.00',
        ];
    }

    public function withDiscount(string $rate): static
    {
        return $this->state(['discount_rate' => $rate]);
    }
}
