<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Money $unit_price
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'unit_price',
        'vat_rate',
        'apply_customer_discount',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class,
            'vat_rate' => 'decimal:2',
            'apply_customer_discount' => 'boolean',
            'stock_quantity' => 'integer',
        ];
    }

    /**
     * Discount rate a given customer gets on this product. Some products
     * (e.g. already-discounted promotions) opt out of the trade discount.
     */
    public function discountRateFor(Customer $customer): string
    {
        return $this->apply_customer_discount ? (string) $customer->discount_rate : '0.00';
    }

    /** @return HasMany<InventoryMovement, $this> */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
