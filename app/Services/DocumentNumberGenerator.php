<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Generates sequential, per-year document numbers such as INV-2025-000042.
 *
 * Must be called inside a database transaction: the last number of the year
 * is read with a locking read, so two concurrent requests cannot receive the
 * same number (the unique index on `number` is the final safety net).
 */
class DocumentNumberGenerator
{
    /**
     * @param  class-string<Model>  $model
     */
    public function next(string $model, string $prefix, CarbonInterface $date): string
    {
        $base = sprintf('%s-%d-', $prefix, $date->year);

        $last = $model::query()
            ->where('number', 'like', $base.'%')
            ->orderByDesc('number')
            ->lockForUpdate()
            ->value('number');

        $sequence = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        return $base.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
