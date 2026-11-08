<?php

namespace Tests\Concerns;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * A student the way production has them: in a school licensed for some classes,
 * following one board, and placed in a class. Licensing hides every experiment
 * from a student who is not.
 */
trait MakesLicensedStudents
{
    protected function enrolledStudent(int $grade = 9, array $licensed = [9, 10], string $board = 'punjab'): User
    {
        $school = School::create([
            'name' => "Test School {$grade}-".uniqid(),
            'district' => 'Gujrat',
            'board' => $board,
            'licensed_grades' => $licensed,
            'licence_start' => now()->subMonth(),
            'licence_end' => now()->addYear(),
            'student_cap' => 100,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'title' => "Class {$grade}-A",
            'grade_level' => $grade,
            'academic_year' => '2026-2027',
        ]);

        $student = User::factory()->create(['school_id' => $school->id]);
        StudentProfile::create(['user_id' => $student->id, 'class_id' => $class->id]);

        return $student;
    }
}
