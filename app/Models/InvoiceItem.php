<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Money $unit_price
 * @property Money $subtotal
 * @property Money $discount_amount
 * @property Money $tax_amount
 * @property Money $total
 */
class InvoiceItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'free_quantity' => 'integer',
            'unit_price' => MoneyCast::class,
            'discount_rate' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'subtotal' => MoneyCast::class,
            'discount_amount' => MoneyCast::class,
            'tax_amount' => MoneyCast::class,
            'total' => MoneyCast::class,
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<CreditNoteItem, $this> */
    public function creditNoteItems(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
