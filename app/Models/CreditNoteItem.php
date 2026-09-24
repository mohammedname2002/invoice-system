<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $unit_price
 * @property Money $subtotal
 * @property Money $discount_amount
 * @property Money $tax_amount
 * @property Money $total
 */
class CreditNoteItem extends Model
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

    /** @return BelongsTo<CreditNote, $this> */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    /** @return BelongsTo<InvoiceItem, $this> */
    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
