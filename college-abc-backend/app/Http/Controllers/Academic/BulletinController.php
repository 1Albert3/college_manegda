<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Classroom;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BulletinController extends Controller
{
    use ApiResponse;

    /**
     * Générer un bulletin pour un élève
     */
    public function generate(Request $request, $studentId)
    {
        try {
            $validated = $request->validate([
                'semester' => 'required|in:1,2,3',
                'school_year' => 'required|string',
            ]);

            $student = Student::with(['currentEnrollment.classroom', 'parents'])->findOrFail($studentId);
            
            // Récupérer les notes de l'élève
            $grades = $this->getStudentGrades($studentId, $validated['semester'], $validated['school_year']);
            
            // Calculer les moyennes
            $averages = $this->calculateAverages($grades);
            
            // Données pour le bulletin
            $bulletinData = [
                'student' => $student,
                'semester' => $validated['semester'],
                'school_year' => $validated['school_year'],
                'grades' => $grades,
                'averages' => $averages,
                'school_info' => [
                    'name' => 'Collège Privé Wend-Manegda',
                    'address' => 'Ouagadougou, Burkina Faso',
                    'phone' => '+226 25 XX XX XX',
                    'email' => 'contact@college-wend-manegda.bf'
                ],
                'generated_at' => now()->format('d/m/Y à H:i')
            ];

            // Générer le PDF
            $pdf = PDF::loadView('bulletins.template', $bulletinData);
            $pdf->setPaper('A4', 'portrait');

            $filename = "bulletin_{$student->matricule}_S{$validated['semester']}_{$validated['school_year']}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la génération du bulletin: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Prévisualiser un bulletin
     */
    public function preview(Request $request, $studentId)
    {
        try {
            $validated = $request->validate([
                'semester' => 'required|in:1,2,3',
                'school_year' => 'required|string',
            ]);

            $student = Student::with(['currentEnrollment.classroom', 'parents'])->findOrFail($studentId);
            $grades = $this->getStudentGrades($studentId, $validated['semester'], $validated['school_year']);
            $averages = $this->calculateAverages($grades);

            return $this->successResponse([
                'student' => [
                    'id' => $student->id,
                    'matricule' => $student->matricule,
                    'full_name' => $student->full_name,
                    'class' => $student->currentEnrollment?->classroom?->name ?? 'Non inscrit',
                    'date_of_birth' => $student->date_of_birth->format('d/m/Y'),
                ],
                'grades' => $grades,
                'averages' => $averages,
                'semester' => $validated['semester'],
                'school_year' => $validated['school_year']
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la prévisualisation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer les notes d'un élève pour un semestre
     */
    private function getStudentGrades($studentId, $semester, $schoolYear)
    {
        // Simuler des données de notes (à adapter selon votre structure de base de données)
        return [
            [
                'subject' => 'Mathématiques',
                'coefficient' => 4,
                'grades' => [
                    ['type' => 'Devoir 1', 'score' => 15, 'max' => 20],
                    ['type' => 'Devoir 2', 'score' => 12, 'max' => 20],
                    ['type' => 'Composition', 'score' => 14, 'max' => 20],
                ],
                'average' => 13.67,
                'weighted_average' => 13.67 * 4
            ],
            [
                'subject' => 'Français',
                'coefficient' => 4,
                'grades' => [
                    ['type' => 'Devoir 1', 'score' => 16, 'max' => 20],
                    ['type' => 'Devoir 2', 'score' => 14, 'max' => 20],
                    ['type' => 'Composition', 'score' => 15, 'max' => 20],
                ],
                'average' => 15.00,
                'weighted_average' => 15.00 * 4
            ],
            [
                'subject' => 'Anglais',
                'coefficient' => 2,
                'grades' => [
                    ['type' => 'Devoir 1', 'score' => 13, 'max' => 20],
                    ['type' => 'Devoir 2', 'score' => 16, 'max' => 20],
                    ['type' => 'Composition', 'score' => 14, 'max' => 20],
                ],
                'average' => 14.33,
                'weighted_average' => 14.33 * 2
            ],
            [
                'subject' => 'Histoire-Géographie',
                'coefficient' => 3,
                'grades' => [
                    ['type' => 'Devoir 1', 'score' => 12, 'max' => 20],
                    ['type' => 'Devoir 2', 'score' => 15, 'max' => 20],
                    ['type' => 'Composition', 'score' => 13, 'max' => 20],
                ],
                'average' => 13.33,
                'weighted_average' => 13.33 * 3
            ],
            [
                'subject' => 'Sciences Physiques',
                'coefficient' => 3,
                'grades' => [
                    ['type' => 'Devoir 1', 'score' => 14, 'max' => 20],
                    ['type' => 'Devoir 2', 'score' => 11, 'max' => 20],
                    ['type' => 'Composition', 'score' => 13, 'max' => 20],
                ],
                'average' => 12.67,
                'weighted_average' => 12.67 * 3
            ],
            [
                'subject' => 'SVT',
                'coefficient' => 2,
                'grades' => [
                    ['type' => 'Devoir 1', 'score' => 16, 'max' => 20],
                    ['type' => 'Devoir 2', 'score' => 14, 'max' => 20],
                    ['type' => 'Composition', 'score' => 15, 'max' => 20],
                ],
                'average' => 15.00,
                'weighted_average' => 15.00 * 2
            ]
        ];
    }

    /**
     * Calculer les moyennes générales
     */
    private function calculateAverages($grades)
    {
        $totalWeightedSum = 0;
        $totalCoefficients = 0;

        foreach ($grades as $subject) {
            $totalWeightedSum += $subject['weighted_average'];
            $totalCoefficients += $subject['coefficient'];
        }

        $generalAverage = $totalCoefficients > 0 ? $totalWeightedSum / $totalCoefficients : 0;

        // Déterminer la mention
        $mention = '';
        if ($generalAverage >= 16) {
            $mention = 'Très Bien';
        } elseif ($generalAverage >= 14) {
            $mention = 'Bien';
        } elseif ($generalAverage >= 12) {
            $mention = 'Assez Bien';
        } elseif ($generalAverage >= 10) {
            $mention = 'Passable';
        } else {
            $mention = 'Insuffisant';
        }

        return [
            'general_average' => round($generalAverage, 2),
            'total_coefficients' => $totalCoefficients,
            'mention' => $mention,
            'rank' => 1, // À calculer selon le classement de la classe
            'class_size' => 35 // À récupérer depuis la base de données
        ];
    }

    /**
     * Lister les bulletins disponibles pour un élève
     */
    public function index($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            
            // Simuler des bulletins existants
            $bulletins = [
                [
                    'id' => 1,
                    'semester' => 1,
                    'school_year' => '2024-2025',
                    'generated_at' => '2024-12-15 10:30:00',
                    'status' => 'published'
                ],
                [
                    'id' => 2,
                    'semester' => 2,
                    'school_year' => '2024-2025',
                    'generated_at' => null,
                    'status' => 'draft'
                ]
            ];

            return $this->successResponse($bulletins);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des bulletins', 500);
        }
    }
}