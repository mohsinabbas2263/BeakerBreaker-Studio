<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyTierQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['tier_number', 'prompt', 'options', 'correct_option', 'explanation'];

    /** FR-02.5: the answer key is server side only. */
    protected $hidden = ['correct_option'];

    protected function casts(): array
    {
        return ['options' => 'array', 'tier_number' => 'integer', 'correct_option' => 'integer'];
    }

    /** FR-02.1: the four tiers, in order. */
    public const TIERS = [
        1 => 'Basic Lab Rules',
        2 => 'Chemical Handling',
        3 => 'Fire and Heat Safety',
        4 => 'First Aid and Emergency',
    ];
}
