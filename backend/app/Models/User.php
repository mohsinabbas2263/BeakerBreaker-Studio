<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'school_id', 'role', 'display_name', 'email', 'password', 'status', 'avatar_key',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'password' => 'hashed',          // SR-01
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** FR-01.1 and SR-04: the Filament panel is for administrators alone. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::Administrator && $this->isActive();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'user_id');
    }

    /** FR-09.1: the classes a teacher is formally assigned to. */
    public function taughtClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_teachers', 'user_id', 'class_id')
            ->withPivot('assigned_at');
    }

    public function safetyAttempts(): HasMany
    {
        return $this->hasMany(SafetyAttempt::class, 'user_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'user_id');
    }

    public function experimentSessions(): HasMany
    {
        return $this->hasMany(ExperimentSession::class, 'user_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'user_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'user_id');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'user_id');
    }

    /** FR-02.3: the gate every experiment request passes through. */
    public function hasPassedSafetyTier(int $tier): bool
    {
        return $this->safetyAttempts()
            ->where('tier_number', $tier)
            ->where('passed', true)
            ->exists();
    }

    public function highestSafetyTierPassed(): int
    {
        return (int) $this->safetyAttempts()->where('passed', true)->max('tier_number');
    }
}
