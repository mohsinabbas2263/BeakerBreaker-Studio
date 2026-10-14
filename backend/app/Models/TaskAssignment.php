<?php

namespace App\Models;

use App\Enums\AssignmentState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'user_id', 'state', 'state_changed_at'];

    protected function casts(): array
    {
        return ['state' => AssignmentState::class, 'state_changed_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** FR-09.5: the state machine refuses a transition it does not declare. */
    public function transitionTo(AssignmentState $next): void
    {
        if (! $this->state->canMoveTo($next)) {
            throw new RuntimeException(
                "Assignment cannot move from {$this->state->value} to {$next->value} (FR-09.5)."
            );
        }

        $this->update(['state' => $next, 'state_changed_at' => now()]);
    }
}
