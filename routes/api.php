<?php

use App\Http\Controllers\RosterController;
use App\Http\Controllers\RosterTotalsController;
use App\Http\Controllers\RosterValidationController;
use Illuminate\Support\Facades\Route;

Route::name('roster.')->prefix('rosters')->group(function () {
    Route::get('/', [RosterController::class, 'index']);
    Route::get('/{roster}', [RosterController::class, 'show']);
    Route::put('/{roster}/assignments', [RosterController::class, 'upsertAssignments']);
    Route::delete('/{roster}/assignments', [RosterController::class, 'deleteAssignments']);

    Route::get('/{roster}/totals', [RosterTotalsController::class, 'show']);

    Route::post('/{roster}/validate', [RosterValidationController::class, 'validateRoster']);
});

