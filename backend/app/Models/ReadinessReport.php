<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadinessReport extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** FR-08.1: the six dimensions. */
    public const DIMENSIONS = [
        'procedural_accuracy',
        'safety_compliance',
        'reagent_management',
        'time_efficiency',
        'conceptual_understanding',
        'error_recovery',
    ];

    protected $fillable = [
        'subject_user_id', 'requesting_user_id', 'classification', 'narrative',
        'ai_generated', 'generated_at', ...self::DIMENSIONS,
    ];

    protected function casts(): array
    {
        return ['ai_generated' => 'boolean', 'generated_at' => 'datetime'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }
}
