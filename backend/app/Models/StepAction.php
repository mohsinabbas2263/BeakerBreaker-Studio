<?php

namespace App\Models;

use App\Models\Concerns\InsertOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing a student did inside a game step (docs/experiment-engine.md).
 *
 * Insert only: these rows are the evidence the readiness report (FR-08.1) is
 * computed from, so they are never edited or deleted.
 */
class StepAction extends Model
{
    use InsertOnly;

    public const UPDATED_AT = null;

    protected $fillable = [
        'session_id', 'step_id', 'user_id', 'kind', 'action',
        'outcome', 'category', 'mistake_code', 'hinted',
    ];

    protected function casts(): array
    {
        return ['action' => 'array', 'hinted' => 'boolean', 'created_at' => 'datetime'];
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(ExperimentStep::class, 'step_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExperimentSession::class, 'session_id');
    }
}
