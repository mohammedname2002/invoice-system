<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\InvoiceStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property InvoiceStatus $status
 * @property Money $subtotal
 * @property Money $discount_total
 * @property Money $tax_total
 * @property Money $total
 * @property Money $amount_credited
 * @property Money $amount_paid
 */
class Invoice extends Model
{
    /**
     * Only descriptive fields are mass assignable. Numbers, totals and the
     * status are owned by InvoiceService and set explicitly.
     */
    protected $fillable = [
        'customer_id',
        'issue_date',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'discount_rate' => 'decimal:2',
            'subtotal' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'tax_total' => MoneyCast::class,
            'total' => MoneyCast::class,
            'amount_credited' => MoneyCast::class,
            'amount_paid' => MoneyCast::class,
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /** @return HasMany<CreditNote, $this> */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Amount still owed: total minus credit notes minus payments. */
    public function balance(): Money
    {
        return $this->total->subtract($this->amount_credited)->subtract($this->amount_paid);
    }

    /**
     * Lines can be edited only until money has been applied to the invoice.
     * After a payment or credit note exists, corrections go through a credit note.
     */
    public function isLocked(): bool
    {
        return $this->amount_paid->isPositive()
            || $this->amount_credited->isPositive()
            || $this->payments()->exists()
            || $this->creditNotes()->exists();
    }

    /** @param  Builder<Invoice>  $query */
    public function scopeForCustomer(Builder $query, ?int $customerId): void
    {
        $query->when($customerId, fn (Builder $q) => $q->where('customer_id', $customerId));
    }

    /** @param  Builder<Invoice>  $query */
    public function scopeIssuedIn(Builder $query, ?int $month, ?int $year): void
    {
        $query
            ->when($year, fn (Builder $q) => $q->whereYear('issue_date', $year))
            ->when($month, fn (Builder $q) => $q->whereMonth('issue_date', $month));
    }

    /** @param  Builder<Invoice>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', InvoiceStatus::open());
    }
}
