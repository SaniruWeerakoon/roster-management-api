<?php

namespace App\Http\Controllers;

use App\Models\ShiftType;
use Illuminate\Http\Request;

class ShiftTypeController extends Controller
{
    public function index()
    {
        $shiftTypes = ShiftType::query()
            ->orderBy('code')
            ->get();
        return response()->json($shiftTypes);
    }

    public function store(Request $request)
    {
    }

    public function show(ShiftType $shiftType)
    {
        return response()->json([
            'data' => $shiftType,
        ]);
    }

    public function update(Request $request, ShiftType $shiftType)
    {
    }

    public function destroy(ShiftType $shiftType)
    {
    }
}
