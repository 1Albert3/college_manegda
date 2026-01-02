<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

/**
 * Contrôleur des années scolaires
 * TODO: Implémenter les méthodes CRUD
 */
class SchoolYearController extends Controller
{
    public function index()
    {
        $years = SchoolYear::orderByDesc('start_date')->get();
        return response()->json(['data' => $years]);
    }

    public function current()
    {
        $schoolYear = SchoolYear::current();

        if (!$schoolYear) {
            return response()->json([
                'message' => 'Aucune année scolaire courante n\'est définie.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => $schoolYear,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:school_years,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        if ($validated['is_current'] ?? false) {
            SchoolYear::where('is_current', true)->update(['is_current' => false]);
        }

        $schoolYear = SchoolYear::create($validated);

        return response()->json([
            'message' => 'Année scolaire créée avec succès.',
            'data' => $schoolYear,
        ], 201);
    }

    public function show($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);
        return response()->json(['data' => $schoolYear]);
    }

    public function update(Request $request, $id)
    {
        $schoolYear = SchoolYear::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:school_years,name,' . $id,
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_current' => 'boolean',
            'is_locked' => 'boolean',
        ]);

        if ($validated['is_current'] ?? false) {
            SchoolYear::where('is_current', true)->update(['is_current' => false]);
        }

        $schoolYear->update($validated);

        return response()->json([
            'message' => 'Année scolaire mise à jour avec succès.',
            'data' => $schoolYear,
        ]);
    }

    public function destroy($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);

        // Prevent deletion if it's the current year or has data
        if ($schoolYear->is_current) {
            return response()->json(['message' => 'Impossible de supprimer l\'année courante.'], 422);
        }

        $schoolYear->delete();

        return response()->json(['message' => 'Année scolaire supprimée avec succès.']);
    }

    public function setCurrent($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);
        $schoolYear->setAsCurrent();

        return response()->json([
            'message' => 'Année scolaire définie comme courante.',
            'data' => $schoolYear,
        ]);
    }
}
