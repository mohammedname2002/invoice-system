<?php

namespace Tests\Concerns;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\InvoiceService;

trait BuildsInvoices
{
    /**
     * Create an invoice through the real service.
     *
     * @param  list<array{0: Product, 1: int}|array<string, mixed>>  $lines  [product, quantity] pairs or raw item rows
     */
    protected function createInvoice(Customer $customer, array $lines, array $overrides = []): Invoice
    {
        $items = array_map(fn ($line) => isset($line[0]) && $line[0] instanceof Product
            ? [
                'product_id' => $line[0]->id,
                'quantity' => $line[1],
                'free_quantity' => $line[2] ?? 0,
                'unit_price' => $line[0]->unit_price->toDecimal(),
                'vat_rate' => (string) $line[0]->vat_rate,
            ]
            : $line, $lines);

        return app(InvoiceService::class)->create(array_merge([
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'items' => $items,
        ], $overrides));
    }
}
