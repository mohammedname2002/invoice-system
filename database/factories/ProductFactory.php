<?php

namespace Database\Factories;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'unit_price' => Money::of(fake()->randomFloat(2, 5, 500)),
            'vat_rate' => '5.00',
            'apply_customer_discount' => true,
            'stock_quantity' => 100,
        ];
    }

    public function price(string $amount): static
    {
        return $this->state(['unit_price' => Money::of($amount)]);
    }

    public function withoutCustomerDiscount(): static
    {
        return $this->state(['apply_customer_discount' => false]);
    }
}
