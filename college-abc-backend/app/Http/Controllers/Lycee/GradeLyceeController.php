<?php

namespace App\Http\Controllers\Lycee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Lycee\GradeLycee;
use App\Models\Lycee\StudentLycee;
use App\Models\Lycee\SubjectLycee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contrôleur des Notes Lycée
 */
class GradeLyceeController extends Controller
{
    /**
     * Liste des matières pour le lycée
     */
    public function subjects()
    {
        return response()->json(
            SubjectLycee::where('is_active', true)
                ->orderBy('nom')
                ->get()
        );
    }

    /**
     * Saisie de notes groupée (par classe/matière)
     */
    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid|exists:school_lycee.classes_lycee,id',
            'subject_id' => 'required|uuid|exists:school_lycee.subjects_lycee,id',
            'school_year_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
            'type_evaluation' => 'required|in:IE,DS,Comp,TP,CC',
            'date_evaluation' => 'required|date',
            // Liste des notes
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|uuid|exists:school_lycee.students_lycee,id',
            'grades.*.note' => 'required|numeric|min:0|max:20',
            'grades.*.appreciation' => 'nullable|string|max:255',
            // Coefficient spécifique au devoir si différent du standard matière
            'coefficient' => ['nullable', 'integer', 'min:1'],
        ]);

        $subject = SubjectLycee::findOrFail($validated['subject_id']);
        // Récupérer la classe pour connaître le niveau/série et déduire le coeff par défaut
        // Mais ici on stocke juste la note brute, le calcul bulletin fera la pondération.
        // Sauf si 'coefficient' est utilisé pour pondérer ce devoir par rapport aux autres évaluations.

        $coeff = $validated['coefficient'] ?? 1;
        $userId = \Illuminate\Support\Facades\Auth::id();

        // Trouver l'ID du profil enseignant si possible
        $teacherId = \Illuminate\Support\Facades\DB::connection('school_lycee')
            ->table('teachers_lycee')
            ->where('user_id', $userId)
            ->value('id');

        $createdCount = 0;
        try {
            foreach ($validated['grades'] as $item) {
                GradeLycee::updateOrCreate(
                    [
                        'school_year_id' => $validated['school_year_id'],
                        'class_id' => $validated['class_id'],
                        'subject_id' => $validated['subject_id'],
                        'student_id' => $item['student_id'],
                        'trimestre' => $validated['trimestre'],
                        'type_evaluation' => $validated['type_evaluation'],
                        'date_evaluation' => $validated['date_evaluation'],
                    ],
                    [
                        'note_sur_20' => $item['note'],
                        'note_obtenue' => $item['note'],
                        'coefficient' => $coeff,
                        'commentaire' => $item['appreciation'] ?? null,
                        'recorded_by' => $userId,
                        'teacher_id' => $teacherId,
                        'is_published' => true,
                        'published_at' => now(),
                    ]
                );
                $createdCount++;
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Exception DB: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ], 500);
        }

        return response()->json([
            'message' => "$createdCount notes enregistrées.",
        ]);
    }

    /**
     * Liste des notes d'un élève (Vue Parent/Élève)
     */
    public function indexStudent(Request $request, string $studentId)
    {
        $grades = GradeLycee::with('subject')
            ->where('student_id', $studentId)
            ->where('is_published', true)
            ->orderBy('date_evaluation', 'desc')
            ->get()
            ->groupBy('trimestre');

        return response()->json($grades);
    }
}
