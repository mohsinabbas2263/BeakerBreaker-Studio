<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssignmentState;
use App\Http\Controllers\Api\Concerns\ScopesToTaughtClasses;
use App\Models\AuditLog;
use App\Models\Experiment;
use App\Models\Notification;
use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Services\ExperimentAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * M3, wf_S13: task assignment (UC-06) and tracking (UC-06.1).
 *
 * FR-09.1  only to students of a class the teacher is assigned to
 * FR-09.4  to one student, a group, or the whole class, with a future due date
 * FR-09.5  states move by themselves (TaskProgressService, assignments:mark-overdue)
 * FR-09.6  each targeted student is notified when the task is created
 */
class TaskController extends Controller
{
    use ScopesToTaughtClasses;

    public function __construct(protected ExperimentAccessService $access) {}

    /** The teacher's own tasks with a count per state. */
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::where('created_by_user_id', $request->user()->id)
            ->whereIn('class_id', $request->user()->taughtClasses()->pluck('classes.id'))
            ->with(['experiment:id,title,emoji', 'schoolClass:id,title'])
            ->withCount([
                'assignments as total',
                'assignments as assigned' => fn ($q) => $q->where('state', AssignmentState::Assigned),
                'assignments as in_progress' => fn ($q) => $q->where('state', AssignmentState::InProgress),
                'assignments as completed' => fn ($q) => $q->where('state', AssignmentState::Completed),
                'assignments as overdue' => fn ($q) => $q->where('state', AssignmentState::Overdue),
            ])
            ->orderByDesc('due_at')
            ->limit(100)
            ->get();

        return response()->json([
            'tasks' => $tasks->map(fn (Task $t) => $this->summary($t))->values(),
        ]);
    }

    /** UC-06.1: the state of the task for each targeted student. */
    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorizeClass($request, $task->schoolClass);

        $task->load(['experiment:id,title,emoji', 'schoolClass:id,title', 'assignments.user:id,display_name,avatar_key']);

        return response()->json([
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'experiment_title' => $task->experiment?->title,
                'experiment_emoji' => $task->experiment?->emoji,
                'class_title' => $task->schoolClass?->title,
                'due_at' => $task->due_at->toIso8601String(),
                'instructions' => $task->instructions,
            ],
            'students' => $task->assignments
                ->sortBy(fn (TaskAssignment $a) => $a->user?->display_name)
                ->map(fn (TaskAssignment $a) => [
                    'id' => $a->user_id,
                    'display_name' => $a->user?->display_name,
                    'avatar_key' => $a->user?->avatar_key,
                    'state' => $a->state->value,
                    'state_changed_at' => $a->state_changed_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    /**
     * The experiments a teacher may set for a class: published, in the class's
     * board-exam pair as far as the school is licensed, and in its board.
     */
    public function experiments(Request $request, SchoolClass $class): JsonResponse
    {
        $this->authorizeClass($request, $class);

        return response()->json([
            'experiments' => $this->assignable($class)
                ->orderBy('grade_level')->orderBy('title')
                ->get(['id', 'title', 'emoji', 'grade_level', 'topic']),
        ]);
    }

    /** FR-09.4 / UC-06. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'experiment_id' => ['required', 'integer', 'exists:experiments,id'],
            // FR-09.4: a due date that is not in the future is rejected.
            'due_at' => ['required', 'date', 'after:now'],
            'target' => ['required', 'in:class,students'],
            'student_ids' => ['required_if:target,students', 'array', 'min:1', 'max:200'],
            'student_ids.*' => ['integer', 'distinct'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ], [
            'due_at.after' => 'The due date must be in the future.',
            'student_ids.required_if' => 'Choose at least one student.',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $this->authorizeClass($request, $class);

        $experiment = $this->assignable($class)->find($data['experiment_id']);
        if (! $experiment) {
            throw ValidationException::withMessages([
                'experiment_id' => 'This experiment is not available to this class.',
            ]);
        }

        $classStudentIds = $class->studentProfiles()->pluck('user_id');

        if ($data['target'] === 'class') {
            $targets = $classStudentIds;
        } else {
            $targets = collect($data['student_ids']);
            // FR-09.1: every target must be a student of this class.
            $outside = $targets->diff($classStudentIds);
            if ($outside->isNotEmpty()) {
                AuditLog::record($request->user()->id, 'teacher.refused', "class:{$class->id}", [
                    'route' => $request->path(), 'students_outside_class' => $outside->values()->all(),
                ], $request->ip());

                return response()->json([
                    'message' => 'Some of those students are not in this class.',
                    'code' => 'not_assigned',
                ], 403);
            }
        }

        if ($targets->isEmpty()) {
            throw ValidationException::withMessages(['target' => 'This class has no students yet.']);
        }

        $instructions = isset($data['instructions']) ? trim(strip_tags($data['instructions'])) : null;

        $task = DB::transaction(function () use ($request, $class, $experiment, $data, $targets, $instructions) {
            $task = Task::create([
                'created_by_user_id' => $request->user()->id,
                'class_id' => $class->id,
                'experiment_id' => $experiment->id,
                'title' => $experiment->title,
                'instructions' => $instructions ?: null,
                'due_at' => $data['due_at'],
            ]);

            $now = now();
            // Written in the school's time zone; due_at itself is stored in UTC.
            $due = $task->due_at->copy()->setTimezone(config('app.display_timezone'))->format('j M Y, g:i a');

            foreach ($targets as $userId) {
                TaskAssignment::create([
                    'task_id' => $task->id, 'user_id' => $userId,
                    'state' => AssignmentState::Assigned, 'state_changed_at' => $now,
                ]);

                // FR-09.6: the bell polls every 30 s, inside the 60 s the requirement allows.
                Notification::create([
                    'user_id' => $userId,
                    'notification_type' => 'task',
                    'subject' => "New task: {$experiment->title}",
                    'body' => "Due {$due}.".($instructions ? " {$instructions}" : ''),
                    'payload' => ['task_id' => $task->id, 'experiment_id' => $experiment->id],
                    'created_at' => $now,
                ]);
            }

            AuditLog::record($request->user()->id, 'task.created', "task:{$task->id}", [
                'class_id' => $class->id, 'experiment_id' => $experiment->id, 'students' => $targets->count(),
            ], $request->ip());

            return $task;
        });

        $task->load(['experiment:id,title,emoji', 'schoolClass:id,title'])->loadCount('assignments as total');

        return response()->json([
            'task' => array_merge($this->summary($task), ['assigned' => $targets->count()]),
        ], 201);
    }

    // ---- helpers ------------------------------------------------------------

    private function assignable(SchoolClass $class)
    {
        $school = $class->school;
        $grades = array_values(array_filter(
            ExperimentAccessService::GRADE_PAIRS[$class->grade_level] ?? [],
            fn (int $g) => $school?->licensesGrade($g),
        ));

        return Experiment::query()
            ->where('is_published', true)
            ->whereIn('grade_level', $grades)
            ->whereIn('board', [$school?->board ?? 'punjab', 'both']);
    }

    private function summary(Task $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'experiment_title' => $t->experiment?->title,
            'experiment_emoji' => $t->experiment?->emoji,
            'class_id' => $t->class_id,
            'class_title' => $t->schoolClass?->title,
            'due_at' => $t->due_at->toIso8601String(),
            'instructions' => $t->instructions,
            'assigned' => (int) ($t->assigned ?? 0),
            'in_progress' => (int) ($t->in_progress ?? 0),
            'completed' => (int) ($t->completed ?? 0),
            'overdue' => (int) ($t->overdue ?? 0),
            'total' => (int) ($t->total ?? 0),
        ];
    }
}
