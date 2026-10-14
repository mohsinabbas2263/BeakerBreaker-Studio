<?php

namespace App\Enums;

enum AssignmentState: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Overdue = 'overdue';

    /** FR-09.5: the permitted transitions, and no others. */
    public function canMoveTo(self $next): bool
    {
        return match ($this) {
            self::Assigned => in_array($next, [self::InProgress, self::Completed, self::Overdue], true),
            self::InProgress => in_array($next, [self::Completed, self::Overdue], true),
            self::Overdue => in_array($next, [self::InProgress, self::Completed], true),
            self::Completed => false,
        };
    }
}
