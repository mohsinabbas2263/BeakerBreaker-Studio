<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    public const BOARDS = ['punjab' => 'Punjab (PCTB / PECTAA)', 'federal' => 'Federal (FBISE)'];

    public const GRADES = [9 => 'Class 9', 10 => 'Class 10', 11 => 'Class 11 (1st year)', 12 => 'Class 12 (2nd year)'];

    protected $fillable = [
        'name', 'district', 'board', 'licensed_grades', 'licence_start', 'licence_end', 'student_cap',
    ];

    protected function casts(): array
    {
        return [
            'licence_start' => 'date',
            'licence_end' => 'date',
            'student_cap' => 'integer',
            'licensed_grades' => 'array',
        ];
    }

    /** Licensing: the administrator decides which classes' experiments this school gets. */
    public function licensesGrade(?int $grade): bool
    {
        return $grade !== null && in_array($grade, array_map('intval', $this->licensed_grades ?? []), true);
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
