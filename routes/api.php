<?php

use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\RosterTotalsController;
use App\Http\Controllers\RosterValidationController;
use App\Http\Controllers\ShiftTypeController;
use Illuminate\Support\Facades\Route;

Route::name('roster.')->prefix('rosters')->group(function () {
    Route::get('/', [RosterController::class, 'index']);
    Route::get('/{roster}', [RosterController::class, 'show']);
    Route::put('/{roster}/assignments', [RosterController::class, 'upsertAssignments']);
    Route::delete('/{roster}/assignments', [RosterController::class, 'deleteAssignments']);
    Route::get('/{roster}/export', [RosterController::class, 'export']);

    Route::get('/{roster}/totals', [RosterTotalsController::class, 'show']);

    Route::post('/{roster}/validate', [RosterValidationController::class, 'validateRoster']);
});

Route::apiResource('admin_rosters', \App\Http\Controllers\Api\RosterController::class)->parameters([
    'admin_rosters' => 'roster',
]);
Route::apiResource('people', PersonController::class);
Route::apiResource('shift_types', ShiftTypeController::class);

