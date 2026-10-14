<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'district', 'licence_start', 'licence_end', 'student_cap'];

    protected function casts(): array
    {
        return ['licence_start' => 'date', 'licence_end' => 'date', 'student_cap' => 'integer'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'school_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'school_id');
    }

    /** FR-10.5: a licence that has lapsed blocks nothing retroactively, but is reported. */
    public function licenceIsCurrent(): bool
    {
        return now()->betweenIncluded($this->licence_start, $this->licence_end);
    }
}
