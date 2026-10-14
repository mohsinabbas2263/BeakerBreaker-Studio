<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One school, one class, and one account per role, so the API can be exercised
 * the moment the database is migrated.
 *
 * FR-01.6: this seeder is the administrator action. There is no route by which
 * any of these accounts could have created itself.
 *
 * Every password here is a local development password. Change them before this
 * reaches any machine a student can touch.
 */
class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::updateOrCreate(
            ['name' => 'Government High School, Gujrat'],
            [
                'district' => 'Gujrat',
                'licence_start' => now()->startOfYear(),
                'licence_end' => now()->addYear()->endOfYear(),
                'student_cap' => 400,
            ]
        );

        $class = SchoolClass::updateOrCreate(
            ['school_id' => $school->id, 'title' => 'Class 10-A'],
            ['grade_level' => 10, 'academic_year' => '2026-2027']
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@beakerbreakers.test'],
            [
                'school_id' => $school->id,
                'role' => UserRole::Administrator,
                'display_name' => 'System Administrator',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@beakerbreakers.test'],
            [
                'school_id' => $school->id,
                'role' => UserRole::Teacher,
                'display_name' => 'Chemistry Teacher',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        // FR-09.1: the assignment is what gives a teacher reach over a class.
        $class->teachers()->syncWithoutDetaching([$teacher->id => ['assigned_at' => now()]]);

        $students = [
            ['Rabail Mehboob', 'rabail@beakerbreakers.test', 'avatar-girl1'],
            ['Syed Mohsin Abbas', 'mohsin@beakerbreakers.test', 'avatar-boy1'],
            ['Hina Nawaz', 'hina@beakerbreakers.test', 'avatar-girl2'],
            ['Zayn Ali', 'zayn@beakerbreakers.test', 'avatar-boy2'],
        ];

        foreach ($students as [$name, $email, $avatar]) {
            $student = User::updateOrCreate(
                ['email' => $email],
                [
                    'school_id' => $school->id,
                    'role' => UserRole::Student,
                    'display_name' => $name,
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'avatar_key' => $avatar,
                ]
            );

            // FR-06.4: a new profile starts at zero. Seeded points would be a lie
            // the leaderboard then tells on every request.
            StudentProfile::updateOrCreate(
                ['user_id' => $student->id],
                ['class_id' => $class->id]
            );
        }

        AuditLog::record($admin->id, 'seed.demo_school', "school:{$school->id}", [
            'note' => 'Development seed data.',
        ]);
    }
}
