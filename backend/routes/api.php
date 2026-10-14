<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExperimentController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\SafetyController;
use Illuminate\Support\Facades\Route;

/*
 * FR-01.6: there is no POST /register in this file, and there is no controller
 * method it could reach. Accounts come from the administrator panel alone.
 */

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// SR-03: everything below requires a valid access token.
Route::middleware('auth:sanctum')->group(function () {
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
        Route::post('/sessions/{session}/steps', [ExperimentController::class, 'completeStep']);
        Route::post('/steps/{step}/guidance', [ExperimentController::class, 'guidance']);

        Route::get('/experiments/{experiment}/quiz', [QuizController::class, 'show']);
        Route::post('/experiments/{experiment}/quiz', [QuizController::class, 'submit']);

        Route::get('/leaderboard', [LeaderboardController::class, 'index']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::patch('/profile', [ProfileController::class, 'update']);
    });
});
