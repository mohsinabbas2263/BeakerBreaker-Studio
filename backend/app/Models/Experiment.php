<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'topic', 'difficulty', 'grade_level', 'prerequisite_experiment_id',
        'chemical_equation', 'explanatory_note', 'ar_available', 'base_points',
        'estimated_minutes', 'emoji', 'card_gradient', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'ar_available' => 'boolean',
            'is_published' => 'boolean',
            'base_points' => 'integer',
            'grade_level' => 'integer',
            'estimated_minutes' => 'integer',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ExperimentStep::class, 'experiment_id')->orderBy('step_order');
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(ExperimentQuizQuestion::class, 'experiment_id');
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(self::class, 'prerequisite_experiment_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExperimentSession::class, 'experiment_id');
    }

    /** FR-02.1: the tier a student must hold before this experiment opens. */
    public function requiredSafetyTier(): int
    {
        return match ($this->difficulty) {
            'easy' => 1,
            'medium' => 2,
            'hard' => 3,
            default => 1,
        };
    }
}
