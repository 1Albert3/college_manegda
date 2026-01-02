<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Models\College\EnrollmentCollege;
use App\Models\College\StudentCollege;
use App\Models\College\GuardianCollege;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentCollegeController extends Controller
{
    public function index(Request $request)
    {
        $query = EnrollmentCollege::with(['student', 'class', 'schoolYear']);

        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        } else {
            $query->currentYear();
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'prenoms' => 'required|string',
            'date_naissance' => 'required|date',
            'sexe' => 'required|in:M,F',
            'class_id' => 'required|uuid',
            'school_year_id' => 'required|uuid',
            'nationalite' => 'nullable|string',
            'regime' => 'required|in:interne,demi_pensionnaire,externe',
            'mode_paiement' => 'required|in:comptant,tranches_1,tranches_2,tranches_3',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            // Create Student
            $student = StudentCollege::create([
                'nom' => $validated['nom'],
                'prenoms' => $validated['prenoms'],
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $request->lieu_naissance ?? 'Inconnu',
                'sexe' => $validated['sexe'],
                'nationalite' => $validated['nationalite'] ?? 'Burkinabè',
                'statut_inscription' => $request->statut_inscription ?? 'nouveau',
                'matricule' => 'C' . date('y') . mt_rand(1000, 9999),
            ]);

            // Create Guardian
            if ($request->has('pere')) {
                GuardianCollege::create([
                    'student_id' => $student->id,
                    'type' => 'pere', // Required by DB enum
                    'nom_complet' => $request->pere['nom_complet'],
                    'telephone_1' => $request->pere['telephone_1'],
                    'email' => $request->pere['email'] ?? null,
                    'profession' => $request->pere['profession'] ?? null,
                    'adresse_physique' => $request->pere['adresse_physique'] ?? $request->adresse ?? 'Adresse non renseignée', // Required by DB
                    'lien_parente' => 'Père'
                ]);
            }

            // Create Enrollment
            $enrollment = EnrollmentCollege::create([
                'student_id' => $student->id,
                'class_id' => $validated['class_id'],
                'school_year_id' => $validated['school_year_id'],
                'regime' => $validated['regime'],
                'mode_paiement' => $validated['mode_paiement'],
                'statut' => 'validee', // Matches enum
                'date_inscription' => now(),
                'frais_scolarite' => 0,
                'total_a_payer' => 0,
                'montant_final' => 0,
            ]);

            return response()->json([
                'message' => 'Inscription réussie.',
                'student_id' => $student->id,
                'enrollment' => $enrollment
            ], 201);
        });
    }

    public function show($id)
    {
        return response()->json(EnrollmentCollege::with(['student', 'class', 'schoolYear'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $enrollment = EnrollmentCollege::findOrFail($id);
        $enrollment->update($request->all());

        return response()->json([
            'message' => 'Inscription mise à jour.',
            'enrollment' => $enrollment,
        ]);
    }

    public function destroy($id)
    {
        $enrollment = EnrollmentCollege::findOrFail($id);
        $enrollment->delete();

        return response()->json(['message' => 'Inscription annulée.']);
    }
}
