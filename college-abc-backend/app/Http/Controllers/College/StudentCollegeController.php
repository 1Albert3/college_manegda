<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\College\StudentCollege;
use App\Models\College\EnrollmentCollege;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentCollegeController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentCollege::with(['guardians', 'enrollments.class']);

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
        return response()->json(StudentCollege::with(['guardians', 'enrollments.class', 'history'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenoms' => 'required|string|max:150',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'nationalite' => 'nullable|string|max:50',
            'statut_inscription' => 'required|in:nouveau,ancien,transfert',
        ]);

        $student = StudentCollege::create($validated);

        return response()->json([
            'message' => 'Élève Collège créé.',
            'student' => $student,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $student = StudentCollege::findOrFail($id);
        $student->update($request->all());

        return response()->json([
            'message' => 'Élève mis à jour.',
            'student' => $student,
        ]);
    }

    public function destroy(string $id)
    {
        $student = StudentCollege::findOrFail($id);
        $student->delete();

        return response()->json(['message' => 'Élève supprimé.']);
    }
}
