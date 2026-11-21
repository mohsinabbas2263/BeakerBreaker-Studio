<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * UC-18, FR-10.2 and FR-10.3: student accounts from a delimited file.
 *
 * The file has a header row and three columns, in any order: name, email, class.
 * "class" is the class title exactly as it appears in the panel (for example
 * "Class 10-A"). Up to 500 data rows (FR-10.2).
 *
 * Every row is checked before anything is written. One failing row and no account
 * is made from the file (FR-10.3); each failing row is reported with its line
 * number and every reason it failed.
 */
class StudentImportService
{
    public const MAX_ROWS = 500;

    public const COLUMNS = ['name', 'email', 'class'];

    /**
     * @return array{created:int, errors:list<array{line:int, messages:list<string>}>}
     */
    public function import(School $school, string $contents, ?User $actor): array
    {
        [$rows, $fileErrors] = $this->parse($contents);

        if ($fileErrors !== []) {
            return ['created' => 0, 'errors' => $fileErrors];
        }

        $errors = $this->validate($school, $rows);

        if ($errors !== []) {
            return ['created' => 0, 'errors' => $errors];
        }

        $classes = $school->classes()->pluck('id', 'title')
            ->mapWithKeys(fn ($id, $title) => [mb_strtolower(trim($title)) => $id]);

        // SR-01: nobody knows this password. One hash for the file, because 500
        // hashes at work factor 12 would not finish inside FR-10.2's 60 seconds;
        // each student chooses their own through the welcome email.
        $unknown = Hash::make(Str::random(40));

        $created = DB::transaction(function () use ($rows, $school, $classes, $unknown, $actor) {
            $users = [];

            foreach ($rows as $row) {
                $user = User::create([
                    'school_id' => $school->id,
                    'role' => UserRole::Student,
                    'display_name' => $row['name'],
                    'email' => $row['email'],
                    'password' => $unknown,
                    'status' => 'active',
                    'avatar_key' => 'avatar-girl1',
                ]);

                StudentProfile::create([
                    'user_id' => $user->id,
                    'class_id' => $classes[mb_strtolower($row['class'])],
                ]);

                // FR-10.8: every account operation is recorded, imported or not.
                AuditLog::record($actor?->id, 'user.created', "user:{$user->id}", [
                    'role' => 'student',
                    'via' => 'import',
                ]);

                $users[] = $user;
            }

            AuditLog::record($actor?->id, 'users.imported', "school:{$school->id}", [
                'count' => count($users),
            ]);

            return $users;
        });

        // UC-18 step 3: a welcome message to each new student. Queued, and sent
        // only once the accounts are committed.
        foreach ($created as $user) {
            $user->notify(new WelcomeNotification);
        }

        return ['created' => count($created), 'errors' => []];
    }

    /**
     * @return array{0: list<array{line:int, name:string, email:string, class:string}>, 1: list<array{line:int, messages:list<string>}>}
     */
    private function parse(string $contents): array
    {
        // Excel saves a byte order mark at the start of a UTF-8 file.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $contents));

        if ($lines === false || $lines === [''] || $lines === []) {
            return [[], [['line' => 1, 'messages' => ['The file is empty.']]]];
        }

        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), str_getcsv($lines[0], $delimiter, '"', ''));

        $missing = array_diff(self::COLUMNS, $header);
        if ($missing !== []) {
            return [[], [[
                'line' => 1,
                'messages' => ['The first row must name the columns name, email and class. Missing: '.implode(', ', $missing).'.'],
            ]]];
        }

        $index = array_flip($header);
        $rows = [];

        foreach (array_slice($lines, 1) as $i => $line) {
            if (trim($line) === '') {
                continue;                                    // blank lines are not rows
            }

            $cells = str_getcsv($line, $delimiter, '"', '');
            $rows[] = [
                'line' => $i + 2,
                'name' => trim((string) ($cells[$index['name']] ?? '')),
                'email' => mb_strtolower(trim((string) ($cells[$index['email']] ?? ''))),
                'class' => trim((string) ($cells[$index['class']] ?? '')),
            ];
        }

        if ($rows === []) {
            return [[], [['line' => 2, 'messages' => ['The file has a header row but no students.']]]];
        }

        if (count($rows) > self::MAX_ROWS) {
            return [[], [[
                'line' => $rows[self::MAX_ROWS]['line'],
                'messages' => ['A file may hold at most '.self::MAX_ROWS.' students; this one has '.count($rows).'. Split it into smaller files.'],
            ]]];
        }

        return [$rows, []];
    }

    /** @return list<array{line:int, messages:list<string>}> */
    private function validate(School $school, array $rows): array
    {
        $classTitles = $school->classes()->pluck('title')
            ->map(fn ($t) => mb_strtolower(trim($t)))->flip();

        $emails = array_column($rows, 'email');
        $taken = User::withTrashed()->whereIn('email', $emails)->pluck('email')
            ->map(fn ($e) => mb_strtolower($e))->flip();
        $seen = [];
        $errors = [];

        foreach ($rows as $row) {
            $messages = [];

            if ($row['name'] === '') {
                $messages[] = 'The name is empty.';
            } elseif (mb_strlen($row['name']) > 60) {
                $messages[] = 'The name is longer than 60 characters.';
            }

            if ($row['email'] === '') {
                $messages[] = 'The email is empty.';
            } elseif (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $messages[] = "\"{$row['email']}\" is not an email address.";
            } elseif (isset($taken[$row['email']])) {
                $messages[] = "{$row['email']} already has an account.";
            } elseif (isset($seen[$row['email']])) {
                $messages[] = "{$row['email']} is also on line {$seen[$row['email']]}.";
            }

            if ($row['email'] !== '') {
                $seen[$row['email']] ??= $row['line'];
            }

            if ($row['class'] === '') {
                $messages[] = 'The class is empty.';
            } elseif (! isset($classTitles[mb_strtolower($row['class'])])) {
                $messages[] = "There is no class called \"{$row['class']}\" in this school. Make it under Classes first.";
            }

            if ($messages !== []) {
                $errors[] = ['line' => $row['line'], 'messages' => $messages];
            }
        }

        return $errors;
    }
}
