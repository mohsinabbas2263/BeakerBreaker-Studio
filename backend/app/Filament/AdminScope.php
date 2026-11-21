<?php

namespace App\Filament;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * SR-04 in the administrator panel: a school administrator sees and changes only
 * its own school. Every resource narrows its query through here, so a record of
 * another school cannot be listed, opened, edited or deleted, even by a crafted
 * request, because Filament resolves every record through the resource query.
 */
final class AdminScope
{
    public static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /** Null for a team administrator, who is not limited to one school. */
    public static function schoolId(): ?int
    {
        $user = self::user();

        return $user?->school_id === null ? null : (int) $user->school_id;
    }

    public static function isTeam(): bool
    {
        return (bool) self::user()?->isTeamAdministrator();
    }

    /** Limits a query on a table that has its own school_id column. */
    public static function bySchoolColumn(Builder $query, string $column = 'school_id'): Builder
    {
        $schoolId = self::schoolId();

        return $schoolId === null ? $query : $query->where($column, $schoolId);
    }

    /** Limits a query through a relation whose model has a school_id column. */
    public static function throughRelation(Builder $query, string $relation): Builder
    {
        $schoolId = self::schoolId();

        return $schoolId === null
            ? $query
            // A deleted account's certificates and audit entries stay with its school.
            : $query->whereHas($relation, fn (Builder $q) => $q
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->where('school_id', $schoolId));
    }
}
