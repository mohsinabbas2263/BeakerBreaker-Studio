<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'device_token', 'platform', 'registered_at'];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
