<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends RecordPolicy
{
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->canManageRecords();
    }
}
