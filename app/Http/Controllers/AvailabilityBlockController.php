<?php

namespace App\Http\Controllers;

use App\Models\AvailabilityBlock;
use Illuminate\Http\Request;

class AvailabilityBlockController extends Controller
{
    public function index(Request $request)
    {
        $query = AvailabilityBlock::query()->with('person');

        if ($request->filled('person_id')) {
            $query->where('person_id', $request->integer('person_id'));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'reason' => ['required', 'string'],
        ]);

        $availabilityBlock = AvailabilityBlock::query()->create($validated);
        return response()->json([
            'message' => 'Availability block created successfully.',
            'data' => $availabilityBlock,
        ], 201);
    }

    public function show(AvailabilityBlock $availabilityBlock)
    {
        return response()->json([
            'data' => $availabilityBlock,
        ]);
    }

    public function update(Request $request, AvailabilityBlock $availabilityBlock)
    {
    }

    public function destroy(AvailabilityBlock $availabilityBlock)
    {
        $availabilityBlock->delete();
        return response()->json([
            'message' => 'Availability block deleted successfully.',
        ]);
    }
}
