<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id', 'step_order', 'title', 'instruction_text',
        'preauthored_guidance', 'item_emoji', 'action_label',
    ];

    protected function casts(): array
    {
        return ['step_order' => 'integer'];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id');
    }
}
