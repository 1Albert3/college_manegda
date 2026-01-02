<?php

namespace App\Services\College;

use App\Models\College\ClassCollege;
use App\Models\College\GradeCollege;
use App\Models\College\ReportCardCollege;
use App\Models\College\StudentCollege;
use App\Models\College\SubjectCollege;
use App\Models\SchoolYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Service de génération des bulletins Collège (6eme-3eme)
 */
class ReportCardCollegeService
{
    public function generateForStudent(
        string $studentId,
        string $classId,
        string $schoolYearId,
        int $trimestre
    ) {
        // Logique similaire au Lycée mais avec Subjects 'College'
        // et Coeffs par niveau (6eme etc.)

        $bulletin = ReportCardCollege::firstOrNew([
            'student_id' => $studentId,
            'class_id' => $classId,
            'school_year_id' => $schoolYearId,
            'trimestre' => $trimestre,
        ]);

        $class = ClassCollege::findOrFail($classId);
        $niveau = $class->niveau; // '6eme', '5eme'...

        $grades = GradeCollege::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->where('trimestre', $trimestre)
            ->with('subject')
            ->get()
            ->groupBy('subject_id');

        $calculator = app('burkina.grading');
        $dataMatieres = [];
        $totalPoints = 0;
        $totalCoeffs = 0;

        foreach ($grades as $subjectId => $subjectGrades) {
            $subject = $subjectGrades->first()->subject;
            $coeff = $subject->getCoefficientForLevel($niveau);

            if ($coeff <= 0) continue;

            $valeurs = $subjectGrades->pluck('note_sur_20')->toArray();
            $poids = $subjectGrades->pluck('coefficient')->toArray();

            $moyenne = $calculator->calculateAverage($valeurs, $poids);
            $points = round($moyenne * $coeff, 2);

            $dataMatieres[] = [
                'code' => $subject->code,
                'nom' => $subject->nom,
                'moyenne' => $moyenne,
                'coefficient' => $coeff,
                'points' => $points,
                'details' => $subjectGrades->map(fn($g) => ['note' => $g->note_sur_20])->toArray()
            ];

            $totalPoints += $points;
            $totalCoeffs += $coeff;
        }

        $moyenneGenerale = ($totalCoeffs > 0) ? round($totalPoints / $totalCoeffs, 2) : 0;

        $mention = $this->calculateMention($moyenneGenerale);

        $bulletin->fill([
            'data_matieres' => $dataMatieres,
            'total_points' => $totalPoints,
            'total_coefficients' => $totalCoeffs,
            'moyenne_generale' => $moyenneGenerale,
            'mention' => $mention,
            'effectif_classe' => $class->effectif_actuel ?? 0,

            'moyenne_classe' => 0,
            'moyenne_premier' => 0,
            'moyenne_dernier' => 0,
            'rang' => 0,

            'is_published' => false
        ]);

        $bulletin->save();

        // Générer le PDF
        $this->generatePDF($bulletin);

        return $bulletin;
    }

    /**
     * Générer le fichier PDF physique
     */
    public function generatePDF(ReportCardCollege $bulletin): string
    {
        $bulletin->load(['student', 'class', 'schoolYear']);

        $data = [
            'bulletin' => $bulletin,
            'student' => $bulletin->student,
            'class' => $bulletin->class,
            'schoolYear' => $bulletin->schoolYear,
            'school_year' => $bulletin->schoolYear?->name,
            'semester' => $bulletin->trimestre,
            'grades' => $bulletin->data_matieres,
            'matieres' => $bulletin->data_matieres,
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

        $filename = "bulletins/college/{$bulletin->school_year_id}/T{$bulletin->trimestre}/{$bulletin->student->matricule}.pdf";

        Storage::disk('public')->put($filename, $pdf->output());

        $bulletin->pdf_path = $filename;
        $bulletin->save();

        return $filename;
    }

    /**
     * Calculer les statistiques de classe (Rangs, Moyenne classe, etc.)
     */
    public function calculateClassStatistics(string $classId, int $trimestre, string $schoolYearId): void
    {
        $bulletins = ReportCardCollege::where('class_id', $classId)
            ->where('trimestre', $trimestre)
            ->where('school_year_id', $schoolYearId)
            ->get();

        if ($bulletins->isEmpty()) return;

        $moyennes = $bulletins->pluck('moyenne_generale')->toArray();
        $effectif = count($moyennes);

        if ($effectif === 0) return;

        $moyenneClasse = round(array_sum($moyennes) / $effectif, 2);
        $moyennePremier = max($moyennes);
        $moyenneDernier = min($moyennes);

        // Trier par moyenne décroissante pour le rang
        $sortedBulletins = $bulletins->sortByDesc('moyenne_generale')->values();

        $rang = 1;
        foreach ($sortedBulletins as $bulletin) {
            $bulletin->update([
                'rang' => $rang,
                'effectif_classe' => $effectif,
                'moyenne_classe' => $moyenneClasse,
                'moyenne_premier' => $moyennePremier,
                'moyenne_dernier' => $moyenneDernier
            ]);
            $rang++;
        }
    }

    private function calculateMention(float $moyenne): string
    {
        if ($moyenne >= 18) return 'Excellent';
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Insuffisant';
    }
}
