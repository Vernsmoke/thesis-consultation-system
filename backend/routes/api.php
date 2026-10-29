<?php

use Illuminate\Support\Facades\Route;

// Public liveness only: database and notification delivery are checked separately.
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'project' => 'Thesis Consultation Reminder System',
    'week' => 13,
    'milestone' => 'foundation',
]));
