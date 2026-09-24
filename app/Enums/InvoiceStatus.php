<?php

namespace App\Enums;

use App\Support\Money;
use Carbon\CarbonInterface;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';

    /**
     * Derive the status from the invoice's money position and due date.
     *
     * The status is never edited by hand: it is a function of what has been
     * invoiced, credited and paid, so it cannot drift out of sync.
     */
    public static function resolve(
        Money $total,
        Money $settled,
        ?CarbonInterface $dueDate,
        CarbonInterface $today,
    ): self {
        $balance = $total->subtract($settled);

        if (! $balance->isPositive()) {
            return self::Paid;
        }

        if ($dueDate !== null && $dueDate->copy()->startOfDay()->lt($today->copy()->startOfDay())) {
            return self::Overdue;
        }

        return $settled->isPositive() ? self::PartiallyPaid : self::Unpaid;
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Unpaid, self::PartiallyPaid, self::Overdue];
    }

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
        };
    }

    /** Tailwind classes for the status badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-slate-100 text-slate-700 ring-slate-500/20',
            self::PartiallyPaid => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            self::Paid => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Overdue => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        };
    }
}
