<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

// Public liveness only: database and notification delivery are checked separately.
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'project' => 'Thesis Consultation Reminder System',
    'week' => 13,
    'milestone' => 'foundation',
]));

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/automation', [AutomationController::class, 'index']);
    Route::get('/automation/{automationItem}', [AutomationController::class, 'show']);
    Route::post('/automation/{automationItem}/recover', [AutomationController::class, 'recover']);
    Route::get('/notifications', [AutomationController::class, 'notifications']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/advisers', [ConsultationController::class, 'advisers']);
    Route::get('/consultations', [ConsultationController::class, 'index']);
    Route::post('/consultations', [ConsultationController::class, 'store']);
    Route::get('/consultations/{consultation}', [ConsultationController::class, 'show']);
    Route::patch('/consultations/{consultation}/schedule', [ConsultationController::class, 'confirmSchedule']);
    Route::patch('/consultations/{consultation}/status', [WorkflowController::class, 'recordOutcome']);
    Route::post('/consultations/{consultation}/action-items', [WorkflowController::class, 'addActionItem']);
    Route::patch('/action-items/{actionItem}/complete', [WorkflowController::class, 'completeActionItem']);
    Route::post('/consultations/{consultation}/follow-ups', [WorkflowController::class, 'followUp']);
});
