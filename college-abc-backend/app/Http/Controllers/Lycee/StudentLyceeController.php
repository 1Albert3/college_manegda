<?php

namespace App\Http\Controllers\Lycee;

use App\Http\Controllers\Controller;
use App\Models\Lycee\StudentLycee;
use Illuminate\Http\Request;

class StudentLyceeController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentLycee::with(['guardians', 'enrollments.class']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenoms', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($request->has('classe_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('class_id', $request->classe_id);
            });
        }

        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function show(string $id)
    {
        return response()->json(StudentLycee::with(['guardians', 'enrollments.class'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenoms' => 'required|string|max:150',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'statut_inscription' => 'required|in:nouveau,ancien,transfert',
        ]);

        $student = StudentLycee::create($validated);

        return response()->json([
            'message' => 'Élève Lycée créé.',
            'student' => $student,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $student = StudentLycee::findOrFail($id);
        $student->update($request->all());

        return response()->json([
            'message' => 'Élève mis à jour.',
            'student' => $student,
        ]);
    }

    public function destroy(string $id)
    {
        $student = StudentLycee::findOrFail($id);
        $student->delete();

        return response()->json(['message' => 'Élève supprimé.']);
    }
}
