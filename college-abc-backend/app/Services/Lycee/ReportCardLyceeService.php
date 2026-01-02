<?php

namespace App\Services\Lycee;

use App\Models\Lycee\ClassLycee;
use App\Models\Lycee\GradeLycee;
use App\Models\Lycee\ReportCardLycee;
use App\Models\Lycee\StudentLycee;
use App\Models\Lycee\SubjectLycee;
use App\Models\SchoolYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Service de génération des bulletins Lycée
 * Gère la complexité des séries et des coefficients variables
 */
class ReportCardLyceeService
{
    /**
     * Générer le bulletin pour un élève (Lycée)
     */
    public function generateForStudent(
        string $studentId,
        string $classId,
        string $schoolYearId,
        int $trimestre
    ): ReportCardLycee {

        // 1. Récupérer l'élève et sa classe (pour connaître Série et Niveau)
        $student = StudentLycee::findOrFail($studentId);
        $class = ClassLycee::findOrFail($classId);

        // 2. Initialiser le bulletin (ou update)
        $bulletin = ReportCardLycee::firstOrNew([
            'student_id' => $studentId,
            'class_id' => $classId,
            'school_year_id' => $schoolYearId,
            'trimestre' => $trimestre,
        ]);

        // 3. Récupérer toutes les notes du trimestre
        $allGrades = GradeLycee::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->where('trimestre', $trimestre)
            ->with('subject') // Optimisation
            ->get()
            ->groupBy('subject_id');

        // 4. Calcul par matière
        $calculator = app('burkina.grading');
        $dataMatieres = [];
        $totalPoints = 0;
        $totalCoeffs = 0;

        foreach ($allGrades as $subjectId => $grades) {
            $subject = $grades->first()->subject;
            $coeff = $subject->getCoefficientFor($class->niveau, $class->serie);

            if ($coeff <= 0) continue;

            $valeurs = [];
            $poids = [];

            foreach ($grades as $g) {
                $valeurs[] = $g->note_sur_20;
                $poids[] = $g->coefficient > 0 ? $g->coefficient : 1;
            }

            $moyenneMatiere = $calculator->calculateAverage($valeurs, $poids);
            $pointsMatiere = round($moyenneMatiere * $coeff, 2);

            $dataMatieres[] = [
                'code' => $subject->code,
                'nom' => $subject->nom,
                'moyenne' => $moyenneMatiere,
                'coefficient' => $coeff,
                'points' => $pointsMatiere,
                'details' => $grades->map(fn($g) => [
                    'note' => $g->note_sur_20,
                    'type' => $g->type_evaluation
                ])->toArray()
            ];

            $totalPoints += $pointsMatiere;
            $totalCoeffs += $coeff;
        }

        // 5. Calcul Moyenne Générale
        $moyenneGenerale = 0;
        if ($totalCoeffs > 0) {
            $moyenneGenerale = round($totalPoints / $totalCoeffs, 2);
        }

        // 6. Sauvegarde
        $bulletin->fill([
            'data_matieres' => $dataMatieres,
            'total_points' => $totalPoints,
            'total_coefficients' => $totalCoeffs,
            'moyenne_generale' => $moyenneGenerale,
            'effectif_classe' => $class->effectif_actuel ?? 0,

            // Valeurs par défaut pour éviter les erreurs SQL strict mode
            // Ces valeurs seront mises à jour lors du calcul des stats de classe (batch)
            'moyenne_classe' => 0,
            'moyenne_premier' => 0,
            'moyenne_dernier' => 0,
            'rang' => 0,

            'is_validated' => false,
        ]);

        $bulletin->save();

        // 7. Générer le PDF
        $this->generatePDF($bulletin);

        return $bulletin;
    }

    /**
     * Générer le fichier PDF physique
     */
    public function generatePDF(ReportCardLycee $bulletin): string
    {
        $bulletin->load(['student', 'class', 'schoolYear']);

        $data = [
            'bulletin' => $bulletin,
            'student' => $bulletin->student,
            'class' => $bulletin->class,
            'schoolYear' => $bulletin->schoolYear, // View expects $schoolYear as object or $school_year as string
            'school_year' => $bulletin->schoolYear?->name,
            'semester' => $bulletin->trimestre,
            'grades' => $bulletin->data_matieres,
            'matieres' => $bulletin->data_matieres, // Compatibility
            'averages' => [
                'total_coefficients' => $bulletin->total_coefficients,
                'total_weighted_sum' => $bulletin->total_points,
                'general_average' => $bulletin->moyenne_generale,
                'rank' => $bulletin->rang,
                'class_size' => $bulletin->effectif_classe
            ],
            'school_info' => [
                'name' => config('app.school_name', 'WEND-MANEGDA')
            ],
            'etablissement' => [
                'nom' => config('app.school_name', 'WEND-MANEGDA')
            ]
        ];

        $pdf = Pdf::loadView('pdf.bulletin', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "bulletins/lycee/{$bulletin->school_year_id}/T{$bulletin->trimestre}/{$bulletin->student->matricule}.pdf";

        Storage::disk('public')->put($filename, $pdf->output());

        $bulletin->pdf_path = $filename;
        $bulletin->save();

        return $filename;
    }

    /**
     * Générer tous les bulletins d'une classe
     * Calcule les statistiques de classe et les rangs
     */
    public function generateForClass(
        string $classId,
        string $schoolYearId,
        int $trimestre
    ): array {
        $class = ClassLycee::findOrFail($classId);

        // 1. Récupérer tous les élèves inscrits dans la classe pour cette année
        $students = StudentLycee::whereHas('enrollments', function ($q) use ($classId, $schoolYearId) {
            $q->where('class_id', $classId)
                ->where('school_year_id', $schoolYearId)
                ->where('statut', 'validee');
        })->where('is_active', true)->get();

        if ($students->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Aucun élève actif dans cette classe',
                'count' => 0
            ];
        }

        // 2. Générer le bulletin de chaque élève (sans stats de classe pour l'instant)
        $bulletins = [];
        foreach ($students as $student) {
            $bulletin = $this->generateForStudent(
                $student->id,
                $classId,
                $schoolYearId,
                $trimestre
            );
            $bulletins[] = $bulletin;
        }

        // 3. Calculer les statistiques de classe
        $moyennes = collect($bulletins)->pluck('moyenne_generale')->filter(fn($m) => $m > 0);

        $effectif = count($bulletins);
        $moyenneClasse = $moyennes->isNotEmpty() ? round($moyennes->avg(), 2) : 0;
        $moyennePremier = $moyennes->isNotEmpty() ? round($moyennes->max(), 2) : 0;
        $moyenneDernier = $moyennes->isNotEmpty() ? round($moyennes->min(), 2) : 0;

        // 4. Calculer les rangs (tri décroissant par moyenne générale)
        $bulletinsSorted = collect($bulletins)->sortByDesc('moyenne_generale')->values();

        $rang = 1;
        $previousMoyenne = null;
        $sameRankCount = 0;

        foreach ($bulletinsSorted as $index => $bulletin) {
            if ($previousMoyenne !== null && $bulletin->moyenne_generale < $previousMoyenne) {
                $rang = $index + 1;
            }

            // Mise à jour du bulletin avec les stats de classe
            $bulletin->update([
                'effectif_classe' => $effectif,
                'moyenne_classe' => $moyenneClasse,
                'moyenne_premier' => $moyennePremier,
                'moyenne_dernier' => $moyenneDernier,
                'rang' => $rang,
            ]);

            $previousMoyenne = $bulletin->moyenne_generale;
        }

        // 5. Régénérer tous les PDFs avec les stats mises à jour
        $pdfPaths = [];
        foreach ($bulletins as $bulletin) {
            $bulletin->refresh(); // Recharger les données mises à jour
            $pdfPath = $this->generatePDF($bulletin);
            $pdfPaths[] = $pdfPath;
        }

        // 6. Mettre à jour l'effectif de la classe
        $class->effectif_actuel = $effectif;
        $class->save();

        return [
            'success' => true,
            'message' => "Bulletins générés avec succès pour la classe {$class->nom}",
            'count' => $effectif,
            'stats' => [
                'effectif' => $effectif,
                'moyenne_classe' => $moyenneClasse,
                'moyenne_premier' => $moyennePremier,
                'moyenne_dernier' => $moyenneDernier,
            ],
            'pdf_paths' => $pdfPaths
        ];
    }
}
