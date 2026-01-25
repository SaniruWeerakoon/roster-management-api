<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Roster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterTotalsController extends Controller
{
    public function show(Request $request, Roster $roster)
    {
        // Optional query flags (default true)
        $includeDaily = $request->boolean('daily', true);
        $includePersonDaily = $request->boolean('person_daily');

        $perPerson = Assignment::query()
            ->join('shift_types', 'shift_types.id', '=', 'assignments.shift_type_id')
            ->where('assignments.roster_id', $roster->id)
            ->groupBy('assignments.person_id')
            ->select([
                'assignments.person_id as person_id',
                DB::raw('COUNT(*) as shifts_count'),
                DB::raw('COALESCE(SUM(shift_types.weight), 0) as load_sum'),
            ])
            ->get()
            ->keyBy('person_id');

        $perPersonOut = [];
        foreach ($perPerson as $personId => $row) {
            $perPersonOut[(int)$personId] = [
                'shifts_count' => (int)$row->shifts_count,
                'load_sum' => (float)$row->load_sum,
            ];
        }

        $perShiftType = Assignment::query()
            ->where('roster_id', $roster->id)
            ->groupBy('shift_type_id')
            ->select([
                'shift_type_id',
                DB::raw('COUNT(*) as shifts_count'),
            ])
            ->get()
            ->keyBy('shift_type_id');

        $perShiftTypeOut = [];
        foreach ($perShiftType as $shiftTypeId => $row) {
            $perShiftTypeOut[(int)$shiftTypeId] = [
                'shifts_count' => (int)$row->shifts_count,
            ];
        }

        $perDayOut = null;
        if ($includeDaily) {
            $perDay = Assignment::query()
                ->where('roster_id', $roster->id)
                ->groupBy('date')
                ->select([
                    'date',
                    DB::raw('COUNT(*) as shifts_count'),
                ])
                ->orderBy('date')
                ->get();

            $perDayOut = [];
            foreach ($perDay as $row) {
                $perDayOut[$row->date->toDateString()] = [
                    'shifts_count' => (int)$row->shifts_count,
                ];
            }
        }

        $personDailyOut = null;
        if ($includePersonDaily) {
            $personDaily = DB::table('assignments')
                ->join('shift_types', 'shift_types.id', '=', 'assignments.shift_type_id')
                ->where('assignments.roster_id', $roster->id)
                ->groupBy('assignments.person_id', 'assignments.date')
                ->select([
                    'assignments.person_id as person_id',
                    'assignments.date as date',
                    DB::raw('COUNT(*) as shifts_count'),
                    DB::raw('COALESCE(SUM(shift_types.weight), 0) as load_sum'),
                ])
                ->orderBy('assignments.date')
                ->get();

            $personDailyOut = [];
            foreach ($personDaily as $row) {
                $pid = (int)$row->person_id;
                if (!isset($personDailyOut[$pid])) $personDailyOut[$pid] = [];
                $personDailyOut[$pid][$row->date->toDateString()] = [
                    'shifts_count' => (int)$row->shifts_count,
                    'load_sum' => (float)$row->load_sum,
                ];
            }
        }

        return response()->json([
            'roster' => [
                'id' => $roster->id,
                'month' => $roster->month->toDateString(),
                'name' => $roster->name,
            ],
            'totals' => [
                'per_person' => $perPersonOut,
                'per_shift_type' => $perShiftTypeOut,
                'per_day' => $perDayOut,                 // null if daily=false
                'per_person_day' => $personDailyOut,     // null if person_daily=false
            ],
        ]);
    }

}

