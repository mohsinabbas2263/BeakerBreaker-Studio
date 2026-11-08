<?php

namespace App\Services;

use App\Enums\AssignmentState;
use App\Models\TaskAssignment;
use App\Models\User;

/**
 * FR-09.5: an assignment changes state with no teacher action.
 *
 *  - The student opens the task's experiment: Assigned or Overdue becomes In Progress.
 *  - The student finishes every step of it: the assignment becomes Completed.
 *  - The due date passes first: MarkOverdueAssignments moves it to Overdue.
 *
 * "Finishes" means the experiment session is complete (FR-03.4), the same point at
 * which its quiz opens. A late finish still completes the task, and the response
 * shows its completion time beside the due date so the teacher can see it was late.
 */
class TaskProgressService
{
    public function experimentStarted(User $student, int $experimentId): void
    {
        $this->move($student, $experimentId,
            [AssignmentState::Assigned, AssignmentState::Overdue], AssignmentState::InProgress);
    }

    public function experimentCompleted(User $student, int $experimentId): void
    {
        $this->move($student, $experimentId,
            [AssignmentState::Assigned, AssignmentState::InProgress, AssignmentState::Overdue], AssignmentState::Completed);
    }

    /** @param list<AssignmentState> $from */
    private function move(User $student, int $experimentId, array $from, AssignmentState $to): void
    {
        TaskAssignment::where('user_id', $student->id)
            ->whereIn('state', $from)
            ->whereHas('task', fn ($q) => $q->where('experiment_id', $experimentId))
            ->get()
            ->each(fn (TaskAssignment $a) => $a->transitionTo($to));
    }
}
