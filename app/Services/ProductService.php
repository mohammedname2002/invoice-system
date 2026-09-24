<?php

namespace App\Services;

use App\Models\Product;
use App\Support\Money;

class ProductService
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data): Product
    {
        $product = new Product;
        $this->fill($product, $data);
        $product->stock_quantity = (int) ($data['stock_quantity'] ?? 0);
        $product->save();

        return $product;
    }

    /**
     * Stock is not edited here: after creation it only moves through
     * invoices and credit notes (see InventoryService).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $this->fill($product, $data);
        $product->save();

        return $product;
    }

    /**
     * Invoice and credit note lines keep their own description and prices,
     * so deleting a product never changes an issued document.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /** @param  array<string, mixed>  $data */
    private function fill(Product $product, array $data): void
    {
        $product->fill([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'vat_rate' => $data['vat_rate'],
            'apply_customer_discount' => (bool) ($data['apply_customer_discount'] ?? false),
        ]);
        $product->unit_price = Money::of($data['unit_price']);
    }
}
