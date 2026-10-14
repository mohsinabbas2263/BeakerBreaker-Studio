<?php

namespace App\Models\Concerns;

use RuntimeException;

/**
 * FR-02.8 and FR-10.8: certificates and audit entries are insert only.
 *
 * The guarantee is written here rather than trusted to reviewers remembering it.
 * Any update or delete on a model using this trait raises instead of writing,
 * so a mistake surfaces as a failing test, not as lost history.
 */
trait InsertOnly
{
    public static function bootInsertOnly(): void
    {
        static::updating(function ($model) {
            throw new RuntimeException(
                class_basename($model).' rows are insert only (FR-02.8, FR-10.8).'
            );
        });

        static::deleting(function ($model) {
            throw new RuntimeException(
                class_basename($model).' rows may not be deleted (FR-02.8, FR-10.8).'
            );
        });
    }
}
