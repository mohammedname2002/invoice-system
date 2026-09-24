<?php

namespace App\Enums;

enum UserRole: string
{
    /** Full access, including deleting records. */
    case Admin = 'admin';

    /** Day-to-day bookkeeping: create and edit documents, record payments. */
    case Accountant = 'accountant';

    /** Read-only access. */
    case Viewer = 'viewer';

    public function canManageRecords(): bool
    {
        return $this === self::Admin || $this === self::Accountant;
    }

    public function canDeleteRecords(): bool
    {
        return $this === self::Admin;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
