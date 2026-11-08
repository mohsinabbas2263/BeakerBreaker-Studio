<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesToTaughtClasses;
use App\Models\AuditLog;
use App\Models\ExperimentSession;
use App\Models\ReadinessReport;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * M4: UC-05 (teacher generates), UC-13 (student views own), wf_S12.
 *
 * FR-08.2  generated on a teacher's request
 * FR-08.4  refused under 3 completed experiments, with the reason
 * FR-08.6  a student sees only their own report (SR-08)
 * FR-09.1  a teacher reaches only students of a class they are assigned to
 */
class ReadinessController extends Controller
{
    use ScopesToTaughtClasses;

    public function __construct(protected ReadinessService $readiness) {}

    /** UC-13 / FR-08.6: the signed-in student's latest report, and nobody else's. */
    public function mine(Request $request): JsonResponse
    {
        $student = $request->user();
        $report = $this->latest($student->id);

        if ($report) {
            return response()->json(['report' => $this->present($report, $student)]);
        }

        $completed = $this->readiness->completedCount($student);

        if ($completed < ReadinessService::MIN_COMPLETED) {
            return $this->notEligible($completed);
        }

        return response()->json([
            'message' => 'Your teacher has not generated a readiness report for you yet.',
            'code' => 'no_report',
        ], 404);
    }

    /** wf_S12: each student of the class with their latest report and eligibility. */
    public function forClass(Request $request, SchoolClass $class): JsonResponse
    {
        $this->authorizeClass($request, $class);

        $profiles = $class->studentProfiles()->with('user:id,display_name,avatar_key')->get()
            ->filter(fn (StudentProfile $p) => $p->user !== null);
        $ids = $profiles->pluck('user_id');

        $completed = ExperimentSession::whereIn('user_id', $ids)->whereNotNull('completed_at')
            ->selectRaw('user_id, COUNT(DISTINCT experiment_id) as n')->groupBy('user_id')
            ->pluck('n', 'user_id');

        $latest = ReadinessReport::whereIn('subject_user_id', $ids)
            ->orderBy('generated_at')->orderBy('id')->get()->keyBy('subject_user_id');

        return response()->json([
            'class' => ['id' => $class->id, 'title' => $class->title],
            'required' => ReadinessService::MIN_COMPLETED,
            'students' => $profiles
                ->sortBy(fn (StudentProfile $p) => $p->user->display_name)
                ->map(fn (StudentProfile $p) => [
                    'id' => $p->user_id,
                    'display_name' => $p->user->display_name,
                    'avatar_key' => $p->user->avatar_key,
                    'completed' => (int) ($completed[$p->user_id] ?? 0),
                    'report' => isset($latest[$p->user_id]) ? $this->present($latest[$p->user_id], $p->user) : null,
                ])->values(),
        ]);
    }

    /** UC-05 / FR-08.2: generate a new report for one student. */
    public function generate(Request $request, User $student): JsonResponse
    {
        $classId = $student->profile?->class_id;
        $class = $student->isStudent() && $classId ? SchoolClass::find($classId) : null;

        if (! $class || ! $this->teaches($request->user(), $class->id)) {
            AuditLog::record($request->user()->id, 'teacher.refused', "user:{$student->id}", [
                'route' => $request->path(),
            ], $request->ip());

            return response()->json([
                'message' => 'This student is not in a class you are assigned to.',
                'code' => 'not_assigned',
            ], 403);
        }

        $completed = $this->readiness->completedCount($student);
        if ($completed < ReadinessService::MIN_COMPLETED) {
            return $this->notEligible($completed);
        }

        $report = $this->readiness->generate($student, $request->user());

        AuditLog::record($request->user()->id, 'readiness.generated', "user:{$student->id}", [
            'report_id' => $report->id, 'classification' => $report->classification, 'ai' => $report->ai_generated,
        ], $request->ip());

        return response()->json(['report' => $this->present($report, $student)], 201);
    }

    // ---- helpers ------------------------------------------------------------

    private function latest(int $studentId): ?ReadinessReport
    {
        return ReadinessReport::where('subject_user_id', $studentId)
            ->orderByDesc('generated_at')->orderByDesc('id')->first();
    }

    /** FR-08.4: the reason is stated, with the numbers. */
    private function notEligible(int $completed): JsonResponse
    {
        $required = ReadinessService::MIN_COMPLETED;
        $left = $required - $completed;

        return response()->json([
            'message' => "A readiness report needs {$required} completed experiments; {$left} more to go.",
            'code' => 'not_eligible',
            'completed' => $completed,
            'required' => $required,
        ], 422);
    }

    private function present(ReadinessReport $r, User $student): array
    {
        return [
            'id' => $r->id,
            'generated_at' => $r->generated_at?->toIso8601String(),
            'ai_generated' => $r->ai_generated,
            'classification' => $r->classification,
            'narrative' => $r->narrative,
            'dimensions' => collect(ReadinessReport::DIMENSIONS)->mapWithKeys(fn ($d) => [$d => (int) $r->{$d}])->all(),
            // Two dimensions are estimated until the experiment engine records each step.
            'estimated' => ['reagent_management', 'error_recovery'],
            'student' => ['id' => $student->id, 'display_name' => $student->display_name, 'avatar_key' => $student->avatar_key],
        ];
    }
}
