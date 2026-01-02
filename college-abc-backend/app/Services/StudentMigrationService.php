<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentMigrationService
{
    /**
     * Conditions de passage selon le programme burkinabé
     */
    const PROMOTION_RULES = [
        'CM2' => [
            'target_level' => '6ème',
            'target_db' => 'school_college',
            'conditions' => [
                'min_average' => 10.00,
                'cep_status' => 'admis'
            ]
        ],
        '3ème' => [
            'target_level' => '2nde',
            'target_db' => 'school_lycee',
            'conditions' => [
                'bepc_status' => 'admis',
                'council_decision' => 'passage'
            ]
        ]
    ];

    /**
     * Migration Primaire → Collège
     */
    public function migratePrimaryToCollege(int $studentId): array
    {
        return DB::transaction(function () use ($studentId) {
            // 1. Vérifier conditions CM2
            $student = DB::connection('school_mp')
                ->table('students_mp')
                ->where('id', $studentId)
                ->where('current_level', 'CM2')
                ->first();

            if (!$student) {
                throw new \Exception('Élève CM2 non trouvé');
            }

            // 2. Vérifier moyenne annuelle ≥ 10
            $yearlyAverage = $this->calculateYearlyAverage($studentId, 'school_mp');
            if ($yearlyAverage < 10.00) {
                return [
                    'success' => false,
                    'message' => "Moyenne insuffisante: {$yearlyAverage}/20. Minimum requis: 10.00"
                ];
            }

            // 3. Vérifier statut CEP
            $cepStatus = DB::connection('school_mp')
                ->table('exam_results')
                ->where('student_id', $studentId)
                ->where('exam_type', 'CEP')
                ->value('status');

            if ($cepStatus !== 'admis') {
                return [
                    'success' => false,
                    'message' => 'CEP non validé. Statut: ' . ($cepStatus ?? 'non passé')
                ];
            }

            // 4. Migration vers school_college
            $newStudentId = $this->transferStudentData($student, 'school_mp', 'school_college');

            // 5. Archivage dans MP
            DB::connection('school_mp')
                ->table('students_mp')
                ->where('id', $studentId)
                ->update([
                    'status' => 'graduated',
                    'graduation_date' => now(),
                    'final_average' => $yearlyAverage
                ]);

            // 6. Log historique
            $this->logTransfer($studentId, 'CM2', '6ème', $yearlyAverage);

            return [
                'success' => true,
                'message' => 'Migration CM2 → 6ème réussie',
                'new_student_id' => $newStudentId,
                'final_average' => $yearlyAverage
            ];
        });
    }

    /**
     * Migration Collège → Lycée
     */
    public function migrateCollegeToLycee(int $studentId, string $targetSerie): array
    {
        return DB::transaction(function () use ($studentId, $targetSerie) {
            // 1. Vérifier élève 3ème
            $student = DB::connection('school_college')
                ->table('students_college')
                ->where('id', $studentId)
                ->where('current_level', '3ème')
                ->first();

            if (!$student) {
                throw new \Exception('Élève 3ème non trouvé');
            }

            // 2. Vérifier décision conseil de classe
            $councilDecision = DB::connection('school_college')
                ->table('council_decisions')
                ->where('student_id', $studentId)
                ->where('year', now()->year)
                ->value('decision');

            if ($councilDecision !== 'passage') {
                return [
                    'success' => false,
                    'message' => 'Passage non autorisé par le conseil de classe'
                ];
            }

            // 3. Vérifier BEPC
            $bepcStatus = DB::connection('school_college')
                ->table('exam_results')
                ->where('student_id', $studentId)
                ->where('exam_type', 'BEPC')
                ->value('status');

            if ($bepcStatus !== 'admis') {
                return [
                    'success' => false,
                    'message' => 'BEPC non validé'
                ];
            }

            // 4. Valider série cible
            if (!in_array($targetSerie, ['A', 'C', 'D'])) {
                throw new \Exception('Série invalide. Autorisées: A, C, D');
            }

            // 5. Migration vers school_lycee
            $studentData = (array) $student;
            $studentData['serie'] = $targetSerie;
            $studentData['current_level'] = '2nde';
            
            $newStudentId = DB::connection('school_lycee')
                ->table('students_lycee')
                ->insertGetId($studentData);

            // 6. Archivage collège
            DB::connection('school_college')
                ->table('students_college')
                ->where('id', $studentId)
                ->update([
                    'status' => 'graduated',
                    'graduation_date' => now(),
                    'target_serie' => $targetSerie
                ]);

            // 7. Log historique
            $this->logTransfer($studentId, '3ème', '2nde', null, $targetSerie);

            return [
                'success' => true,
                'message' => "Migration 3ème → 2nde série {$targetSerie} réussie",
                'new_student_id' => $newStudentId,
                'serie' => $targetSerie
            ];
        });
    }

    /**
     * Calcul moyenne annuelle avec arrondi strict
     */
    private function calculateYearlyAverage(int $studentId, string $connection): float
    {
        $grades = DB::connection($connection)
            ->table('grades')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->where('grades.student_id', $studentId)
            ->where('grades.year', now()->year)
            ->select('grades.score', 'subjects.coefficient')
            ->get();

        if ($grades->isEmpty()) {
            return 0.00;
        }

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($grades as $grade) {
            $totalPoints += $grade->score * $grade->coefficient;
            $totalCoefficients += $grade->coefficient;
        }

        // Arrondi strict à 2 décimales
        return round($totalPoints / $totalCoefficients, 2);
    }

    /**
     * Transfert des données élève entre bases
     */
    private function transferStudentData(object $student, string $fromDb, string $toDb): int
    {
        $studentData = (array) $student;
        
        // Nettoyer les champs spécifiques à l'ancienne base
        unset($studentData['id'], $studentData['created_at'], $studentData['updated_at']);
        
        // Ajouter timestamp
        $studentData['created_at'] = now();
        $studentData['updated_at'] = now();

        return DB::connection($toDb)
            ->table(str_replace('school_', 'students_', $toDb))
            ->insertGetId($studentData);
    }

    /**
     * Log historique des transferts
     */
    private function logTransfer(int $studentId, string $fromLevel, string $toLevel, ?float $average = null, ?string $serie = null): void
    {
        DB::connection('school_core')
            ->table('student_transfers')
            ->insert([
                'student_id' => $studentId,
                'from_level' => $fromLevel,
                'to_level' => $toLevel,
                'final_average' => $average,
                'target_serie' => $serie,
                'transferred_at' => now(),
                'academic_year' => now()->year . '-' . (now()->year + 1)
            ]);

        Log::info("Student migration completed", [
            'student_id' => $studentId,
            'from' => $fromLevel,
            'to' => $toLevel,
            'average' => $average,
            'serie' => $serie
        ]);
    }
}