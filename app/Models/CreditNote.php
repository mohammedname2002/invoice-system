<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $issue_date
 * @property Money $subtotal
 * @property Money $discount_total
 * @property Money $tax_total
 * @property Money $total
 */
class CreditNote extends Model
{
    protected $fillable = [
        'issue_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'subtotal' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'tax_total' => MoneyCast::class,
            'total' => MoneyCast::class,
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return HasMany<CreditNoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
