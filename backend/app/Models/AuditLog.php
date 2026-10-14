<?php

namespace App\Models;

use App\Models\Concerns\InsertOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FR-10.8: insert only, retained seven years.
 */
class AuditLog extends Model
{
    use InsertOnly;

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id', 'action_type', 'target_reference', 'context', 'ip_address', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public static function record(
        ?int $actorId,
        string $action,
        ?string $target = null,
        array $context = [],
        ?string $ip = null,
    ): self {
        return self::create([
            'actor_user_id' => $actorId,
            'action_type' => $action,
            'target_reference' => $target,
            'context' => $context ?: null,
            'ip_address' => $ip,
            'occurred_at' => now(),
        ]);
    }
}
