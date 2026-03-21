<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index()
    {
        $people = Person::query()->latest()->get();//->paginate(10);

        return response()->json($people);
    }

    public function store(Request $request)
    {
        $validated = request()->validate([
            'code' => ['required', 'string', 'max:50', 'unique:people,code'],
            'name' => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ]);
        $person = Person::query()->create($validated);

        return response()->json([
            'message' => 'Person created successfully.',
            'data' => $person,
        ], 201);
    }

    public function show(Person $person)
    {
        return response()->json([
            'data' => $person,
        ]);
    }

    public function update(Request $request, Person $person)
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:people,code,' . $person->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $person->update($validated);

        return response()->json([
            'message' => 'Person updated successfully.',
            'data' => $person->fresh(),
        ]);
    }

    public function destroy(Person $person)
    {
        $person->active = false;
        $person->save();

        return response()->json([
            'message' => 'Person deactivated successfully.',
        ]);
    }
}
