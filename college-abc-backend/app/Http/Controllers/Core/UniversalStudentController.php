<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolYear;
use App\Models\MP\StudentMP;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;
use App\Models\MP\ClassMP;
use App\Models\College\ClassCollege;
use App\Models\Lycee\ClassLycee;
use App\Models\MP\EnrollmentMP;
use App\Models\College\EnrollmentCollege;
use App\Models\Lycee\EnrollmentLycee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UniversalStudentController extends Controller
{
    /**
     * Create a student in any cycle based on the class name.
     */
    public function store(Request $request)
    {
        Log::info('UniversalStudentController::store called', $request->all());
        // 1. Validation for the simplified frontend form
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'birth_date' => 'required|date',
            'gender' => 'required|in:M,F',
            'parent_name' => 'required|string',
            'parent_phone' => 'nullable|string',
            'class_name' => 'required|string',
            'status' => 'nullable|string'
        ]);

        $className = $validated['class_name'];

        // 2. Identify Cycle and Class
        $cycle = $this->detectCycle($className);
        $targetClass = $this->findClass($className, $cycle);

        if (!$targetClass) {
            return response()->json([
                'error' => 'Classe introuvable',
                'message' => 'La classe demandée n\'existe pas.'
            ], 422);
        }

        // 3. Create Student and Enrollment in Transaction
        return DB::transaction(function () use ($validated, $cycle, $targetClass) {
            $yearSuffix = date('y');
            $matricule = $yearSuffix . '-' . strtoupper(substr($validated['last_name'], 0, 3)) . '-' . rand(1000, 9999);

            $studentData = [
                'matricule' => $matricule,
                'nom' => $validated['last_name'],
                'prenoms' => $validated['first_name'],
                'date_naissance' => $validated['birth_date'],
                'lieu_naissance' => 'Burkina Faso', // Default
                'sexe' => $validated['gender'],
                'statut_inscription' => 'nouveau',
                'is_active' => true
            ];

            $student = null;
            $enrollment = null;
            $schoolYear = SchoolYear::current();

            if (!$schoolYear) {
                // Fallback attempt to find ANY active year if current() fails
                $schoolYear = SchoolYear::where('is_current', true)->first();
                if (!$schoolYear) {
                    // Last resort: create one or pick the latest
                    $schoolYear = SchoolYear::latest()->first();
                }
            }
            // If still null (unlikely in prod but possible in test env)
            if (!$schoolYear) throw new \Exception('Aucune année scolaire active.');

            if ($cycle === 'mp') {
                $student = StudentMP::create($studentData);
                $enrollment = EnrollmentMP::create([
                    'student_id' => $student->id,
                    'class_id' => $targetClass->id,
                    'school_year_id' => $schoolYear->id,
                    'statut' => $validated['status'] === 'active' ? 'validee' : 'en_attente',
                    'date_inscription' => now(),
                    'type_inscription' => 'reinscription', // Default
                    'regime' => 'externe',
                    'frais_scolarite' => 0,
                    'frais_inscription' => 0,
                    'total_a_payer' => 0,
                    'montant_final' => 0,
                    'mode_paiement' => 'tranches_3'
                ]);
            } elseif ($cycle === 'college') {
                $student = StudentCollege::create($studentData);
                $enrollment = EnrollmentCollege::create([
                    'student_id' => $student->id,
                    'class_id' => $targetClass->id,
                    'school_year_id' => $schoolYear->id,
                    'statut' => $validated['status'] === 'active' ? 'validee' : 'en_attente',
                    'date_inscription' => now(),
                    'regime' => 'externe',
                    'frais_scolarite' => 0,
                    'frais_inscription' => 0,
                    'total_a_payer' => 0,
                    'montant_final' => 0,
                    'mode_paiement' => 'tranches_3'
                ]);
            } elseif ($cycle === 'lycee') {
                $student = StudentLycee::create($studentData);
                $enrollment = EnrollmentLycee::create([
                    'student_id' => $student->id,
                    'class_id' => $targetClass->id,
                    'school_year_id' => $schoolYear->id,
                    'statut' => $validated['status'] === 'active' ? 'validee' : 'en_attente',
                    'date_inscription' => now(),
                    'regime' => 'externe',
                    'frais_scolarite' => 0,
                    'frais_inscription' => 0,
                    'total_a_payer' => 0,
                    'montant_final' => 0,
                    'mode_paiement' => 'tranches_3'
                ]);
            }

            return response()->json([
                'message' => 'Inscription réussie',
                'student' => $student,
                'enrollment' => $enrollment,
                'cycle' => $cycle
            ], 201);
        });
    }

    private function detectCycle($className)
    {
        $normalized = strtoupper($className);
        // CP1, CP2, CE1, CE2, CM1, CM2, PS, MS, GS
        if (preg_match('/^(CP|CE|CM|PS|MS|GS)/', $normalized)) {
            return 'mp';
        }
        // 6eme, 5eme, 4eme, 3eme
        if (preg_match('/[6543].*ME/i', $className) || preg_match('/[6543].*ème/i', $className)) {
            return 'college';
        }
        // 2nd, 1ere, Tle
        if (preg_match('/^(2|1|T)/', $normalized)) {
            return 'lycee';
        }
        return 'college'; // Default fallback
    }

    /**
     * Update student status or details.
     */
    public function update(Request $request, $id)
    {
        try {
            $student = $this->findStudentById($id);

            if (!$student) {
                return response()->json(['message' => 'Élève introuvable.'], 404);
            }

            // Handle Status Update (Valider/Rejeter)
            if ($request->has('status')) {
                $status = $request->status;
                // Map frontend status to backend status
                // Backend values usually: validee, en_attente, rejetee, abandon
                // Frontend values: approved, active, rejected, processing

                $dbStatus = 'en_attente';
                if ($status === 'approved' || $status === 'active') $dbStatus = 'validee';
                if ($status === 'rejected') $dbStatus = 'refusee'; // ENUM: en_attente, validee, refusee

                // Update Enrollment Status
                // Get enrollment based on student type
                $enrollment = null;
                if ($student instanceof StudentCollege) {
                    $enrollment = EnrollmentCollege::where('student_id', $student->id)->orderBy('created_at', 'desc')->first();
                } elseif ($student instanceof StudentMP) {
                    $enrollment = EnrollmentMP::where('student_id', $student->id)->orderBy('created_at', 'desc')->first();
                } elseif ($student instanceof StudentLycee) {
                    $enrollment = EnrollmentLycee::where('student_id', $student->id)->orderBy('created_at', 'desc')->first();
                }

                if ($enrollment) {
                    $enrollment->update(['statut' => $dbStatus]);
                }
            }

            // Handle other updates if needed
            if ($request->has('first_name')) $student->update(['prenoms' => $request->first_name]);
            if ($request->has('last_name')) $student->update(['nom' => $request->last_name]);

            return response()->json([
                'message' => 'Mise à jour effectuée.',
                'student' => $student
            ]);
        } catch (\Exception $e) {
            Log::error('UniversalStudentController::update error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show student details.
     */
    public function show($id)
    {
        $student = $this->findStudentById($id);

        if (!$student) {
            return response()->json(['message' => 'Élève introuvable.'], 404);
        }

        // Load relationships
        $student->load(['enrollments.class']);

        return response()->json($student);
    }

    /**
     * Find student across all cycles.
     */
    private function findStudentById($id)
    {
        $student = StudentMP::find($id);
        if ($student) return $student;

        $student = StudentCollege::find($id);
        if ($student) return $student;

        $student = StudentLycee::find($id);
        if ($student) return $student;

        return null;
    }

    private function findClass($className, $cycle)
    {
        if ($cycle === 'mp') return ClassMP::where('nom', $className)->first();
        if ($cycle === 'college') return ClassCollege::where('nom', $className)->first();
        if ($cycle === 'lycee') return ClassLycee::where('nom', $className)->first();
        return null;
    }
}
