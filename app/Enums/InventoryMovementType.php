<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    /** Stock leaving the warehouse on an invoice. */
    case Sale = 'sale';

    /** Stock coming back through a credit note. */
    case CreditReturn = 'credit_return';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Sale',
            self::CreditReturn => 'Credit return',
        };
    }
}
