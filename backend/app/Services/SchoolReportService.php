<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * FR-10.6: school-level analytics. Certification rate, experiment completion rate,
 * and the distribution of readiness classifications, for one school or, for the
 * project team, every school together.
 *
 * Definitions, so the report says what it measured:
 * - certification rate: students holding a safety certificate (FR-02.7) out of
 *   all active students.
 * - completion rate: experiments completed at least once, out of the experiments
 *   open to each student's class (licensed, published, not turned off, FR-10.4),
 *   summed over students. A student with no class counts for nothing either way.
 * - readiness: each student's most recent readiness report (FR-08.3); students
 *   with none are counted as "no report yet".
 * - active this week: students who signed in during the last 7 days.
 */
class SchoolReportService
{
    /** @return array<string, mixed> */
    public function build(?School $school): array
    {
        $students = User::query()
            ->where('role', 'student')
            ->where('status', 'active')
            ->when($school, fn ($q) => $q->where('school_id', $school->id));

        $studentIds = (clone $students)->pluck('id');
        $studentCount = $studentIds->count();

        $certified = Certificate::whereIn('user_id', $studentIds)->distinct()->count('user_id');

        $classes = SchoolClass::query()
            ->when($school, fn ($q) => $q->where('school_id', $school->id))
            ->with('school')
            ->orderBy('title')
            ->get();

        $rows = [];
        $openTotal = 0;
        $doneTotal = 0;

        foreach ($classes as $class) {
            $open = $class->licensedExperiments()
                ->whereNotIn('id', $class->disabledExperiments()->select('experiments.id'))
                ->pluck('id');

            $members = $class->studentProfiles()
                ->whereIn('user_id', $studentIds)
                ->pluck('user_id');

            $done = $members->isEmpty() || $open->isEmpty() ? 0 : DB::table('experiment_sessions')
                ->whereIn('user_id', $members)
                ->whereIn('experiment_id', $open)
                ->whereNotNull('completed_at')
                ->distinct()
                ->get(['user_id', 'experiment_id'])
                ->count();

            $possible = $members->count() * $open->count();
            $classCertified = Certificate::whereIn('user_id', $members)->distinct()->count('user_id');

            $openTotal += $possible;
            $doneTotal += $done;

            $rows[] = [
                'class' => $class->title,
                'school' => $class->school?->name,
                'students' => $members->count(),
                'experiments_open' => $open->count(),
                'certified' => $classCertified,
                'certification_rate' => self::percent($classCertified, $members->count()),
                'completion_rate' => self::percent($done, $possible),
            ];
        }

        return [
            'school' => $school?->name ?? 'All schools',
            'generated_at' => now(),
            'students' => $studentCount,
            'teachers' => User::where('role', 'teacher')->where('status', 'active')
                ->when($school, fn ($q) => $q->where('school_id', $school->id))->count(),
            'classes' => $classes->count(),
            'certified' => $certified,
            'certification_rate' => self::percent($certified, $studentCount),
            'completion_rate' => self::percent($doneTotal, $openTotal),
            'readiness' => $this->readiness($studentIds),
            'active_this_week' => (clone $students)->where('last_login_at', '>=', now()->subDays(7))->count(),
            'by_class' => $rows,
        ];
    }

    /** @return array{ready:int, developing:int, not_ready:int, none:int} */
    private function readiness($studentIds): array
    {
        $latest = DB::table('readiness_reports')
            ->whereIn('subject_user_id', $studentIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('subject_user_id');

        $counts = DB::table('readiness_reports')
            ->whereIn('id', $latest)
            ->selectRaw('classification, COUNT(*) as n')
            ->groupBy('classification')
            ->pluck('n', 'classification');

        $ready = (int) ($counts['ready'] ?? 0);
        $developing = (int) ($counts['developing'] ?? 0);
        $notReady = (int) ($counts['not_ready'] ?? 0);

        return [
            'ready' => $ready,
            'developing' => $developing,
            'not_ready' => $notReady,
            'none' => max(0, $studentIds->count() - $ready - $developing - $notReady),
        ];
    }

    /** Null when there is nothing to divide by, so the report shows "no data", not 0%. */
    public static function percent(int $part, int $whole): ?float
    {
        return $whole === 0 ? null : round(100 * $part / $whole, 1);
    }

    /** FR-10.7: the report as delimited text. */
    public function toCsv(array $report): string
    {
        $out = fopen('php://temp', 'r+');
        $pct = fn (?float $v) => $v === null ? 'no data' : $v.'%';

        fputcsv($out, ['Beaker Breakers Studio school report'], ',', '"', '');
        fputcsv($out, ['School', self::cell($report['school'])], ',', '"', '');
        fputcsv($out, ['Generated', $report['generated_at']->format('Y-m-d H:i')], ',', '"', '');
        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['Measure', 'Value'], ',', '"', '');
        fputcsv($out, ['Active students', $report['students']], ',', '"', '');
        fputcsv($out, ['Teachers', $report['teachers']], ',', '"', '');
        fputcsv($out, ['Classes', $report['classes']], ',', '"', '');
        fputcsv($out, ['Certified students', $report['certified']], ',', '"', '');
        fputcsv($out, ['Certification rate', $pct($report['certification_rate'])], ',', '"', '');
        fputcsv($out, ['Experiment completion rate', $pct($report['completion_rate'])], ',', '"', '');
        fputcsv($out, ['Readiness: ready', $report['readiness']['ready']], ',', '"', '');
        fputcsv($out, ['Readiness: developing', $report['readiness']['developing']], ',', '"', '');
        fputcsv($out, ['Readiness: not ready', $report['readiness']['not_ready']], ',', '"', '');
        fputcsv($out, ['Readiness: no report yet', $report['readiness']['none']], ',', '"', '');
        fputcsv($out, ['Students signed in during the last 7 days', $report['active_this_week']], ',', '"', '');
        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['Class', 'School', 'Students', 'Experiments open', 'Certified', 'Certification rate', 'Completion rate'], ',', '"', '');

        foreach ($report['by_class'] as $row) {
            fputcsv($out, [
                self::cell($row['class']), self::cell($row['school']), $row['students'], $row['experiments_open'],
                $row['certified'], $pct($row['certification_rate']), $pct($row['completion_rate']),
            ], ',', '"', '');
        }

        rewind($out);

        return self::safe((string) stream_get_contents($out));
    }

    /**
     * FR-10.5 and FR-10.7: the certificate register as delimited text.
     *
     * @param  iterable<Certificate>  $certificates
     */
    public function certificatesCsv(iterable $certificates): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Certificate', 'Student', 'Email', 'Class', 'Tier 1', 'Tier 2', 'Tier 3', 'Tier 4', 'Issued on'], ',', '"', '');

        foreach ($certificates as $c) {
            fputcsv($out, array_map([self::class, 'cell'], [
                $c->certificate_reference,
                $c->user?->display_name,
                $c->user?->email,
                $c->user?->profile?->schoolClass?->title,
                $c->tier1_score, $c->tier2_score, $c->tier3_score, $c->tier4_score,
                $c->issued_on?->format('Y-m-d'),
            ]), ',', '"', '');
        }

        rewind($out);

        return self::safe((string) stream_get_contents($out));
    }

    /**
     * A name typed by a person could start with =, + or @, which a spreadsheet
     * would run as a formula when the file is opened. Such a cell is prefixed
     * with an apostrophe so it stays text.
     */
    public static function cell(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /** Excel reads a file without this mark as the local code page and garbles Urdu names. */
    private static function safe(string $csv): string
    {
        return "\xEF\xBB\xBF".$csv;
    }
}
