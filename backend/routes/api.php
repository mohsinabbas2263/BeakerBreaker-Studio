<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExperimentController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\MissionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReadinessController;
use App\Http\Controllers\Api\SafetyController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeacherClassController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Support\Facades\Route;

/*
 * FR-01.6: there is no POST /register in this file, and there is no controller
 * method it could reach. Accounts come from the administrator panel alone.
 */

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password');

// SR-03: everything below requires a valid access token, and is rate limited per user.
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ---- student ----
    Route::middleware('role:student')->group(function () {
        Route::get('/safety/tiers', [SafetyController::class, 'index']);
        Route::post('/safety/tiers/{tier}/start', [SafetyController::class, 'start'])
            ->whereNumber('tier');
        Route::post('/safety/tiers/{tier}/submit', [SafetyController::class, 'submit'])
            ->whereNumber('tier');
        Route::get('/safety/certificate', [SafetyController::class, 'certificate']);

        Route::get('/experiments', [ExperimentController::class, 'index']);
        Route::get('/experiments/{experiment}', [ExperimentController::class, 'show']);
        Route::post('/experiments/{experiment}/start', [ExperimentController::class, 'start']);
        Route::post('/sessions/{session}/steps', [ExperimentController::class, 'completeStep']);          // confirm steps
        Route::post('/sessions/{session}/steps/{step}/actions', [ExperimentController::class, 'act']);   // game steps
        Route::post('/steps/{step}/guidance', [ExperimentController::class, 'guidance'])
            ->middleware('throttle:guidance');                                                 // paid AI calls

        Route::get('/experiments/{experiment}/quiz', [QuizController::class, 'show']);
        Route::post('/experiments/{experiment}/quiz', [QuizController::class, 'submit']);

        Route::get('/leaderboard', [LeaderboardController::class, 'index']);
        Route::get('/missions', [MissionController::class, 'index']);                       // FR-07.3
        Route::get('/readiness', [ReadinessController::class, 'mine']);                      // FR-08.6
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::patch('/profile', [ProfileController::class, 'update']);

        // M7 – FR-09.6: notification inbox (SR-08: no user-id param).
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('/push/register', [NotificationController::class, 'registerPushToken']);
    });

    // ---- teacher ----
    Route::middleware('role:teacher')->group(function () {
        // FR-09.1: every route below checks the class against class_teachers.
        Route::get('/teacher/dashboard', [TeacherClassController::class, 'dashboard']);
        Route::get('/teacher/classes', [TeacherClassController::class, 'classes']);
        Route::get('/teacher/classes/{class}/students', [TeacherClassController::class, 'monitor']);    // FR-09.2
        Route::get('/teacher/classes/{class}/safety', [TeacherClassController::class, 'safety']);       // FR-02.9
        Route::get('/teacher/classes/{class}/experiments', [TaskController::class, 'experiments']);

        // FR-08: readiness reports (wf_S12).
        Route::get('/teacher/classes/{class}/readiness', [ReadinessController::class, 'forClass']);
        Route::post('/teacher/students/{student}/readiness', [ReadinessController::class, 'generate']);  // FR-08.2

        // FR-09.4, FR-09.5: tasks.
        Route::get('/teacher/tasks', [TaskController::class, 'index']);
        Route::post('/teacher/tasks', [TaskController::class, 'store']);
        Route::get('/teacher/tasks/{task}', [TaskController::class, 'show']);

        // FR-09.7: broadcast a notification to a class.
        Route::post('/teacher/broadcast', [TeacherController::class, 'broadcast']);
        Route::get('/teacher/notifications', [TeacherController::class, 'sent']);
    });
});
