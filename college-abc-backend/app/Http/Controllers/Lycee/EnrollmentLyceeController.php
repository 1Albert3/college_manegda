<?php

namespace App\Http\Controllers\Lycee;

use App\Http\Controllers\Controller;
use App\Models\Lycee\EnrollmentLycee;
use Illuminate\Http\Request;

class EnrollmentLyceeController extends Controller
{
    public function index(Request $request)
    {
        $query = EnrollmentLycee::with(['student', 'class', 'schoolYear']);

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
            'regime' => 'required|in:interne,demi_pensionnaire,externe',
            'mode_paiement' => 'required|in:comptant,tranches_1,tranches_2,tranches_3',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validated) {
            // Create Student
            $student = \App\Models\Lycee\StudentLycee::create([
                'nom' => $validated['nom'],
                'prenoms' => $validated['prenoms'],
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $request->lieu_naissance ?? 'Inconnu',
                'sexe' => $validated['sexe'],
                'statut_inscription' => $request->statut_inscription ?? 'nouveau',
                'matricule' => 'L' . date('y') . mt_rand(1000, 9999),
            ]);

            // Create Guardian
            if ($request->has('pere')) {
                \App\Models\Lycee\GuardianLycee::create([
                    'student_id' => $student->id,
                    'nom_complet' => $request->pere['nom_complet'],
                    'telephone_1' => $request->pere['telephone_1'],
                    'email' => $request->pere['email'] ?? null,
                    'profession' => $request->pere['profession'] ?? null,
                    'lien_parente' => 'Père'
                ]);
            }

            // Create Enrollment
            $enrollment = \App\Models\Lycee\EnrollmentLycee::create([
                'student_id' => $student->id,
                'class_id' => $validated['class_id'],
                'school_year_id' => $validated['school_year_id'],
                'regime' => $validated['regime'],
                'mode_paiement' => $validated['mode_paiement'],
                'statut' => 'valide'
            ]);

            return response()->json([
                'message' => 'Inscription Lycée réussie.',
                'student_id' => $student->id,
                'enrollment' => $enrollment
            ], 201);
        });
    }

    public function show($id)
    {
        return response()->json(EnrollmentLycee::with(['student', 'class', 'schoolYear'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $enrollment = EnrollmentLycee::findOrFail($id);
        $enrollment->update($request->all());

        return response()->json([
            'message' => 'Inscription mise à jour.',
            'enrollment' => $enrollment,
        ]);
    }

    public function destroy($id)
    {
        $enrollment = EnrollmentLycee::findOrFail($id);
        $enrollment->delete();

        return response()->json(['message' => 'Inscription annulée.']);
    }
}
