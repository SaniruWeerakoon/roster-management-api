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
        ]);
        $roster = Roster::query()->create($validated);

        return response()->json([
            'message' => 'Roster created successfully.',
            'data' => $roster,
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
        ]);
        $roster->update($validated);

        return response()->json([
            'message' => 'Roster updated successfully.',
            'data' => $roster->fresh(),
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
