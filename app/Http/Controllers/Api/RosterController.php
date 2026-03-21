<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Roster;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    public function index()
    {
        $rosters = Roster::query()->latest()->get();//->paginate(10);

        return response()->json($rosters);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'month' => ['required', 'date'],
            'shift_type_ids' => ['nullable', 'array'],
            'shift_type_ids.*' => ['integer', 'exists:shift_types,id'],
        ]);

        $roster = Roster::query()->create([
            'name' => $validated['name'],
            'month' => $validated['month'],
        ]);

        $roster->shiftTypes()->sync($validated['shift_type_ids'] ?? []);

        return response()->json([
            'message' => 'Roster created successfully.',
            'data' => $roster->load('shiftTypes'),
        ], 201);
    }

    public function show(Roster $roster)
    {
        return response()->json([
            'data' => $roster,
        ]);
    }

    public function update(Request $request, Roster $roster)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'month' => ['sometimes', 'date'],
            'shift_type_ids' => ['nullable', 'array'],
            'shift_type_ids.*' => ['integer', 'exists:shift_types,id'],
        ]);

        $roster->update([
            'name' => $validated['name'] ?? $roster->name,
            'month' => $validated['month'] ?? $roster->month,
        ]);

        if (array_key_exists('shift_type_ids', $validated)) {
            $roster->shiftTypes()->sync($validated['shift_type_ids']);
        }

        return response()->json([
            'message' => 'Roster updated successfully.',
            'data' => $roster->load('shiftTypes'),
        ]);
    }

    public function destroy(Roster $roster)
    {
//        $roster->delete();
//
//        return response()->json([
//            'message' => 'Roster deleted successfully.',
//        ]);
        return response()->json([
            'message' => 'Roster deletion is not allowed.',
        ], 403);
    }
}
