<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The classes table. The model is SchoolClass because Class is reserved in PHP.
 */
class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = ['school_id', 'title', 'grade_level', 'academic_year'];

    protected function casts(): array
    {
        return ['grade_level' => 'integer'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_teachers', 'class_id', 'user_id')
            ->withPivot('assigned_at');
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'class_id');
    }

    /** FR-10.4: experiments the administrator has turned off for this class. */
    public function disabledExperiments(): BelongsToMany
    {
        return $this->belongsToMany(Experiment::class, 'class_disabled_experiments', 'class_id', 'experiment_id')
            ->withPivot('disabled_by_user_id', 'disabled_at');
    }

    /**
     * The experiments this class may be given at all: its exam pair (9 and 10, or
     * 11 and 12) within the school's licence, of the school's board or both
     * boards, published. Whether one is turned off for the class is separate.
     */
    public function licensedExperiments(): \Illuminate\Database\Eloquent\Builder
    {
        $school = $this->school;
        $grades = array_values(array_filter(
            \App\Services\ExperimentAccessService::GRADE_PAIRS[$this->grade_level] ?? [],
            fn (int $g) => (bool) $school?->licensesGrade($g),
        ));

        return Experiment::query()
            ->where('is_published', true)
            ->whereIn('grade_level', $grades ?: [0])
            ->whereIn('board', [$school?->board ?? 'punjab', 'both']);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'class_id');
    }
}
