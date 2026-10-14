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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'class_id');
    }
}
