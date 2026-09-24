<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared role rules for the bookkeeping records:
 *  - every signed-in user may read,
 *  - admins and accountants may create and edit,
 *  - only admins may delete.
 */
abstract class RecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageRecords();
    }

    public function update(User $user, Model $model): bool
    {
        return $user->canManageRecords();
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->canDeleteRecords();
    }
}
