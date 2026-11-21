<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Onboarding, run by the project team on the server. Makes a school with its
 * board and licence (CLAUDE.md deviation 5) and the school's first administrator,
 * who then makes the school's teachers and students in the panel (UC-17, UC-18).
 *
 * FR-01.6: this is an administrator action, like the seeders. Nobody reaches it
 * without a shell on the server.
 *
 *   php artisan school:onboard "Government High School, Gujrat" --district=Gujrat \
 *       --board=punjab --classes=9,10 --from=2026-12-01 --until=2027-11-30 --cap=400 \
 *       --admin-name="Ms Ayesha" --admin-email=admin@ghs-gujrat.edu.pk
 *
 * With --team and no school, it makes a project team administrator instead.
 */
class OnboardSchool extends Command
{
    protected $signature = 'school:onboard
        {name? : The school name, as it should appear everywhere}
        {--district= : District}
        {--board= : punjab or federal}
        {--classes= : Licensed classes, comma separated, from 9 to 12}
        {--from= : Licence start date, YYYY-MM-DD}
        {--until= : Licence end date, YYYY-MM-DD}
        {--cap=0 : Student cap, 0 for none}
        {--admin-name= : The school administrator\'s display name}
        {--admin-email= : The school administrator\'s email address}
        {--team : Make a project team administrator (no school) instead}
        {--print-link : Print the set-password link here instead of emailing it}';

    protected $description = 'Make a school, its licence and its first administrator (or, with --team, a team administrator)';

    public function handle(): int
    {
        $adminName = $this->option('admin-name') ?: $this->ask('Administrator name');
        $adminEmail = mb_strtolower(trim((string) ($this->option('admin-email') ?: $this->ask('Administrator email'))));

        $adminCheck = Validator::make(
            ['name' => $adminName, 'email' => $adminEmail],
            ['name' => ['required', 'string', 'max:60'], 'email' => ['required', 'email', 'unique:users,email']],
        );

        if ($adminCheck->fails()) {
            return $this->failWith($adminCheck->errors()->all());
        }

        $school = null;
        $fields = [];

        if (! $this->option('team')) {
            $fields = [
                'name' => $this->argument('name') ?: $this->ask('School name'),
                'district' => $this->option('district') ?: $this->ask('District'),
                'board' => $this->option('board') ?: $this->choice('Board', array_keys(School::BOARDS)),
                'licensed_grades' => array_values(array_filter(array_map(
                    'intval',
                    explode(',', (string) ($this->option('classes') ?: $this->ask('Licensed classes, e.g. 9,10')))
                ))),
                'licence_start' => $this->option('from') ?: $this->ask('Licence start (YYYY-MM-DD)'),
                'licence_end' => $this->option('until') ?: $this->ask('Licence end (YYYY-MM-DD)'),
                'student_cap' => (int) $this->option('cap'),
            ];

            $schoolCheck = Validator::make($fields, [
                'name' => ['required', 'string', 'max:160', 'unique:schools,name'],
                'district' => ['required', 'string', 'max:80'],
                'board' => ['required', 'in:'.implode(',', array_keys(School::BOARDS))],
                'licensed_grades' => ['required', 'array', 'min:1'],
                'licensed_grades.*' => ['integer', 'in:'.implode(',', array_keys(School::GRADES))],
                'licence_start' => ['required', 'date_format:Y-m-d'],
                'licence_end' => ['required', 'date_format:Y-m-d', 'after:licence_start'],
                'student_cap' => ['integer', 'min:0'],
            ]);

            if ($schoolCheck->fails()) {
                return $this->failWith($schoolCheck->errors()->all());
            }

            $fields['licensed_grades'] = array_values(array_unique($fields['licensed_grades']));
        }

        $admin = DB::transaction(function () use (&$school, $adminName, $adminEmail, $fields) {
            if (! $this->option('team')) {
                $school = School::create($fields);
                AuditLog::record(null, 'school.onboarded', "school:{$school->id}", [
                    'board' => $school->board,
                    'licensed_grades' => $school->licensed_grades,
                    'licence_end' => $school->licence_end->toDateString(),
                ]);
            }

            // SR-01: a password nobody knows; the welcome link lets its owner choose one.
            $admin = User::create([
                'school_id' => $school?->id,
                'role' => UserRole::Administrator,
                'display_name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make(Str::random(40)),
                'status' => 'active',
            ]);

            AuditLog::record(null, 'user.created', "user:{$admin->id}", [
                'role' => 'administrator',
                'via' => 'school:onboard',
                'team' => $school === null,
            ]);

            return $admin;
        });

        if ($school) {
            $this->info("School made: {$school->name} (id {$school->id}), {$school->board}, classes ".implode(', ', $school->licensed_grades).", licence to {$school->licence_end->toDateString()}.");
        }

        $this->info(($school ? 'School administrator' : 'Team administrator')." made: {$admin->display_name} <{$admin->email}>.");

        if ($this->option('print-link')) {
            $this->line('Set-password link, valid once for 60 minutes. Give it to them privately:');
            $this->line(WelcomeNotification::linkFor($admin));
        } else {
            $admin->notify(new WelcomeNotification);
            $this->info('A welcome email with a set-password link has been sent.');
        }

        return self::SUCCESS;
    }

    private function failWith(array $messages): int
    {
        foreach ($messages as $message) {
            $this->error($message);
        }
        $this->error('Nothing was made.');

        return self::FAILURE;
    }
}
