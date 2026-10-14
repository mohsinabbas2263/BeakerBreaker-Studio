<?php

namespace App\Models;

use App\Models\Concerns\InsertOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FR-02.7 and FR-02.8. Issued once, never revoked, never edited.
 */
class Certificate extends Model
{
    use HasFactory, InsertOnly;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'certificate_reference',
        'tier1_score', 'tier2_score', 'tier3_score', 'tier4_score', 'issued_on',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'tier1_score' => 'integer',
            'tier2_score' => 'integer',
            'tier3_score' => 'integer',
            'tier4_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
