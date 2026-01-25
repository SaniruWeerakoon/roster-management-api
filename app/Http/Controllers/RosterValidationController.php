<?php

namespace App\Http\Controllers;

use App\Models\Roster;
use App\Services\ConstraintService;
use Illuminate\Http\Request;

class RosterValidationController extends Controller
{
    public function validateRoster(Request $request, Roster $roster, ConstraintService $service)
    {
        $result = $service->validateRoster($roster);

        return response()->json([
            'roster' => [
                'id' => $roster->id,
                'month' => $roster->month->toDateString(),
                'name' => $roster->name,
            ],
            'violations' => $result['violations'],
            'stats' => $result['stats'],
//             'totals' => app(RosterTotalsController::class)->show($request, $roster)->getData(true)['totals'],
        ]);
    }

}
