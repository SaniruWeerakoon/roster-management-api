<?php

namespace App\Http\Controllers;

use App\Export\RosterExport;
use App\Models\Assignment;
use App\Models\Person;
use App\Models\Roster;
use App\Models\ShiftType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RosterController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month'); // expects YYYY-MM
        $q = Roster::query()->orderByDesc('month');

        if ($month) {
            // Convert YYYY-MM to YYYY-MM-01
            $q->where('month', $month . '-01');
        }

        return response()->json([
            'data' => $q->get(['id', 'month', 'name', 'created_at', 'updated_at'])
        ]);
    }

    public function show(Roster $roster)
    {
        $people = Person::query()->where('active', true)->orderBy('name')->get(['id', 'code', 'name']);
//        $shiftTypes = ShiftType::query()->orderBy('id')->get(['id', 'code', 'name', 'category', 'weight', 'color']);
        $enabledShiftTypes = $roster->shiftTypes()->get(['shift_types.id', 'shift_types.code', 'shift_types.name']);

        $assignments = Assignment::query()
            ->where('roster_id', $roster->id)
            ->get(['id', 'date', 'person_id', 'shift_type_id']);

        return response()->json([
            'roster' => [
                'id' => $roster->id,
                'month' => $roster->month->toDateString(),
                'name' => $roster->name,
            ],
            'people' => $people,
            'shiftTypes' => $enabledShiftTypes,
            'assignments' => $assignments,
        ]);
    }

    public function upsertAssignments(Request $request, Roster $roster)
    {
        $data = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.date' => ['required', 'date'],
            'assignments.*.person_id' => ['required', 'integer', 'exists:people,id'],
            'assignments.*.shift_type_id' => ['required', 'integer', 'exists:shift_types,id'],
        ]);

        $allowedShiftTypeIds = $roster->shiftTypes()
            ->pluck('shift_types.id')
            ->flip();

        $rows = [];
        $now = now();
        foreach ($data['assignments'] as $a) {
            $shiftTypeId = (int)$a['shift_type_id'];
            if (!isset($allowedShiftTypeIds[$shiftTypeId])) {
                abort(422, 'Shift type not enabled for this roster');
            }

            $rows[] = [
                'roster_id' => $roster->id,
                'date' => $a['date'],
                'person_id' => $a['person_id'],
                'shift_type_id' => $a['shift_type_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Upsert based on the unique key (roster/date/person/shift)
        Assignment::query()->upsert(
            $rows,
            ['roster_id', 'date', 'person_id', 'shift_type_id'],
            ['updated_at']
        );

        return response()->json(['ok' => true]);
    }

    public function deleteAssignments(Request $request, Roster $roster)
    {
        $data = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.date' => ['required', 'date'],
            'assignments.*.person_id' => ['required', 'integer'],
            'assignments.*.shift_type_id' => ['required', 'integer'],
        ]);

        $q = Assignment::query()->where('roster_id', $roster->id);

        // Delete rows by OR-ing conditions (safe for small batches; for huge batches we can optimize)
        $q->where(function ($sub) use ($data) {
            foreach ($data['assignments'] as $a) {
                $sub->orWhere(function ($one) use ($a) {
                    $one->whereDate('date', $a['date'])
                        ->where('person_id', $a['person_id'])
                        ->where('shift_type_id', $a['shift_type_id']);
                });
            }
        });

        $deleted = $q->delete();

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    public function export(Roster $roster)
    {
        $rosterData =(array) $this->show(roster: $roster)->getData(true);
        $fileName = 'roster_' . $roster->name . '.xlsx';
        $excel = new RosterExport();
        $excel->roster = $rosterData;
        return $excel->download($fileName);
    }
}
