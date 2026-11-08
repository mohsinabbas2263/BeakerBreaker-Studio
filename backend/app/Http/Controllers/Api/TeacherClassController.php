<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesToTaughtClasses;
use App\Models\Certificate;
use App\Models\ExperimentSession;
use App\Models\QuizAttempt;
use App\Models\ReadinessReport;
use App\Models\SafetyAttempt;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * M3, the teacher's view of their classes: wf_S10 dashboard, wf_S11 class monitor
 * and wf_S14 safety review. Read only; no record is altered (UC-14 postcondition).
 *
 * FR-09.1 and SR-04: every class is checked against class_teachers on the server.
 */
class TeacherClassController extends Controller
{
    use ScopesToTaughtClasses;

    /** A student who has done nothing for this many days is flagged as inactive. */
    private const INACTIVE_DAYS = 7;

    /** The classes this teacher is formally assigned to, with headline numbers. */
    public function classes(Request $request): JsonResponse
    {
        $classes = $request->user()->taughtClasses()->orderBy('grade_level')->orderBy('title')->get();

        return response()->json([
            'classes' => $classes->map(fn (SchoolClass $class) => $this->classSummary($class))->values(),
        ]);
    }

    /**
     * wf_S10: totals across every class the teacher teaches, and the students who
     * need attention, with the reason.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $classes = $request->user()->taughtClasses()->orderBy('grade_level')->orderBy('title')->get();
        $rows = $classes->flatMap(fn (SchoolClass $class) => $this->studentRows($class)
            ->map(fn (array $row) => $row + ['class_id' => $class->id, 'class_title' => $class->title]));

        $attention = $rows
            ->map(fn (array $row) => $row + ['reasons' => $this->attentionReasons($row)])
            ->filter(fn (array $row) => $row['reasons'] !== [])
            ->sortByDesc(fn (array $row) => count($row['reasons']))
            ->take(10)
            ->map(fn (array $row) => [
                'id' => $row['id'],
                'display_name' => $row['display_name'],
                'avatar_key' => $row['avatar_key'],
                'class_id' => $row['class_id'],
                'class_title' => $row['class_title'],
                'reasons' => $row['reasons'],
            ])
            ->values();

        return response()->json([
            'stats' => [
                'students' => $rows->count(),
                'avg_xp' => (int) round($rows->avg('total_points') ?? 0),
                'certified' => $rows->where('has_certificate', true)->count(),
                'ready_for_lab' => $rows->where('readiness', 'ready')->count(),
            ],
            'classes' => $classes->map(fn (SchoolClass $class) => $this->classSummary($class))->values(),
            'attention' => $attention,
        ]);
    }

    /**
     * FR-09.2 / wf_S11: for each student of the class, points, experiments completed,
     * safety certification and the most recent activity.
     */
    public function monitor(Request $request, SchoolClass $class): JsonResponse
    {
        $this->authorizeClass($request, $class);

        return response()->json([
            'class' => ['id' => $class->id, 'title' => $class->title],
            'students' => $this->studentRows($class)->values(),
        ]);
    }

    /**
     * wf_S14 and UC-26 (FR-02.9): the tier-by-tier pass matrix and each student's
     * certificate, visible to the teacher as soon as it is issued.
     */
    public function safety(Request $request, SchoolClass $class): JsonResponse
    {
        $this->authorizeClass($request, $class);

        $profiles = $class->studentProfiles()->with('user:id,display_name,avatar_key')->get();
        $ids = $profiles->pluck('user_id');

        // Best score per student per tier.
        $best = SafetyAttempt::whereIn('user_id', $ids)
            ->selectRaw('user_id, tier_number, MAX(score) as best, MAX(CASE WHEN passed THEN 1 ELSE 0 END) as ok')
            ->groupBy('user_id', 'tier_number')
            ->get()
            ->groupBy('user_id');

        $certificates = Certificate::whereIn('user_id', $ids)->get()->keyBy('user_id');

        $students = $profiles
            ->sortBy(fn (StudentProfile $p) => $p->user?->display_name)
            ->map(function (StudentProfile $p) use ($best, $certificates) {
                $tiers = $best->get($p->user_id, collect())->keyBy('tier_number');
                $row = [
                    'id' => $p->user_id,
                    'display_name' => $p->user?->display_name,
                    'avatar_key' => $p->user?->avatar_key,
                ];
                foreach ([1, 2, 3, 4] as $n) {
                    $row["tier{$n}_passed"] = (bool) ($tiers->get($n)?->ok ?? false);
                    $row["tier{$n}_score"] = $tiers->has($n) ? (int) $tiers->get($n)->best : null;
                }
                $certificate = $certificates->get($p->user_id);
                $row['certificate'] = $certificate?->certificate_reference;
                $row['certificate_issued_on'] = $certificate?->issued_on?->toDateString();

                return $row;
            })
            ->values();

        return response()->json([
            'class' => ['id' => $class->id, 'title' => $class->title],
            'summary' => [
                'certified' => $students->whereNotNull('certificate')->count(),
                'total' => $students->count(),
            ],
            'students' => $students,
        ]);
    }

    // ---- helpers ------------------------------------------------------------

    private function classSummary(SchoolClass $class): array
    {
        $rows = $this->studentRows($class);

        return [
            'id' => $class->id,
            'title' => $class->title,
            'grade_level' => $class->grade_level,
            'academic_year' => $class->academic_year,
            'student_count' => $rows->count(),
            'avg_xp' => (int) round($rows->avg('total_points') ?? 0),
            'certified_count' => $rows->where('has_certificate', true)->count(),
        ];
    }

    /**
     * One row per student of the class, built with a fixed number of queries so a
     * class of 40 or more stays quick (wf_S11 note).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function studentRows(SchoolClass $class): Collection
    {
        $profiles = $class->studentProfiles()->with('user:id,display_name,avatar_key,status')->get()
            ->filter(fn (StudentProfile $p) => $p->user !== null);
        $ids = $profiles->pluck('user_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        $completed = ExperimentSession::whereIn('user_id', $ids)->whereNotNull('completed_at')
            ->selectRaw('user_id, COUNT(DISTINCT experiment_id) as n')->groupBy('user_id')
            ->pluck('n', 'user_id');

        $tiers = SafetyAttempt::whereIn('user_id', $ids)->where('passed', true)
            ->selectRaw('user_id, MAX(tier_number) as t')->groupBy('user_id')
            ->pluck('t', 'user_id');

        $certified = Certificate::whereIn('user_id', $ids)->pluck('user_id')->flip();

        // The latest readiness classification per student (FR-08, shown in wf_S11).
        $readiness = ReadinessReport::whereIn('subject_user_id', $ids)
            ->orderBy('generated_at')->get(['subject_user_id', 'classification'])
            ->pluck('classification', 'subject_user_id');

        // The latest quiz score per student, for the "low score" flag.
        $lastQuiz = QuizAttempt::whereIn('user_id', $ids)->orderBy('submitted_at')
            ->get(['user_id', 'score'])->pluck('score', 'user_id');

        return $profiles
            ->sortBy(fn (StudentProfile $p) => $p->user->display_name)
            ->map(fn (StudentProfile $p) => [
                'id' => $p->user_id,
                'display_name' => $p->user->display_name,
                'avatar_key' => $p->user->avatar_key,
                'total_points' => (int) $p->total_points,
                'rank_level' => (int) $p->rank_level,
                'rank_name' => $p->rankName(),
                'experiments_completed' => (int) ($completed[$p->user_id] ?? 0),
                'safety_tier_passed' => (int) ($tiers[$p->user_id] ?? 0),
                'has_certificate' => $certified->has($p->user_id),
                'readiness' => $readiness[$p->user_id] ?? null,
                'last_quiz_score' => isset($lastQuiz[$p->user_id]) ? (int) $lastQuiz[$p->user_id] : null,
                'last_active_on' => $p->last_active_on?->toDateString(),
            ]);
    }

    /** @return list<string> why this student needs the teacher's attention, if at all */
    private function attentionReasons(array $row): array
    {
        $reasons = [];

        if ($row['last_active_on'] === null) {
            $reasons[] = 'Not started yet';
        } elseif (now()->startOfDay()->diffInDays($row['last_active_on'], true) >= self::INACTIVE_DAYS) {
            $reasons[] = 'Inactive for '.(int) now()->startOfDay()->diffInDays($row['last_active_on'], true).' days';
        }

        if (! $row['has_certificate']) {
            $reasons[] = $row['safety_tier_passed'] === 0
                ? 'Safety Tier 1 not passed'
                : "Not safety certified (tier {$row['safety_tier_passed']} of 4)";
        }

        if ($row['last_quiz_score'] !== null && $row['last_quiz_score'] < 80) {
            $reasons[] = "Low quiz score ({$row['last_quiz_score']}%)";
        }

        return $reasons;
    }
}
