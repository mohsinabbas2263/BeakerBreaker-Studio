<?php

namespace App\Console\Commands;

use App\Enums\AssignmentState;
use App\Models\TaskAssignment;
use Illuminate\Console\Command;

/**
 * FR-09.5: an assignment passes to overdue when the due date passes,
 * with no teacher action.
 */
class MarkOverdueAssignments extends Command
{
    protected $signature = 'assignments:mark-overdue';

    protected $description = 'Move assigned and in-progress task assignments to overdue once the due date has passed (FR-09.5)';

    public function handle(): int
    {
        $moved = 0;

        TaskAssignment::query()
            ->whereIn('state', [AssignmentState::Assigned, AssignmentState::InProgress])
            ->whereHas('task', fn ($q) => $q->where('due_at', '<', now()))
            ->chunkById(200, function ($assignments) use (&$moved) {
                foreach ($assignments as $assignment) {
                    $assignment->transitionTo(AssignmentState::Overdue);
                    $moved++;
                }
            });

        $this->info("{$moved} assignment(s) marked overdue.");

        return self::SUCCESS;
    }
}
