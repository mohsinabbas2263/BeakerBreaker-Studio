<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/**
 * FR-09.1 and SR-04: a teacher acts only on a class they are formally assigned to,
 * and the check is made here, on the server, for every teacher request.
 * A refusal is written to the audit log (UC-06 alternate flow, FR-10.8).
 */
trait ScopesToTaughtClasses
{
    protected function teaches(User $teacher, int $classId): bool
    {
        return $teacher->taughtClasses()->whereKey($classId)->exists();
    }

    /** Aborts with 403 not_assigned unless the caller teaches this class. */
    protected function authorizeClass(Request $request, SchoolClass $class): void
    {
        if ($this->teaches($request->user(), $class->id)) {
            return;
        }

        AuditLog::record($request->user()->id, 'teacher.refused', "class:{$class->id}", [
            'route' => $request->path(),
        ], $request->ip());

        throw new HttpResponseException(response()->json([
            'message' => 'You are not assigned to this class.',
            'code' => 'not_assigned',
        ], 403));
    }
}
