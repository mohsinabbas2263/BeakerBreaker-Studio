<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_user_id', 'class_id', 'experiment_id', 'title', 'instructions', 'due_at',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'task_id');
    }
}
