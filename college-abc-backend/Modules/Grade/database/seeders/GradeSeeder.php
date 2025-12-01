<?php

namespace Modules\Grade\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Grade\Entities\Evaluation;
use Modules\Grade\Entities\Grade;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\Subject;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\TeacherSubject;
use Modules\Core\Entities\User;
use Modules\Student\Entities\Student;
use Modules\Student\Entities\Enrollment;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seedage du module Grades...');

        $currentYear = AcademicYear::current()->first() ?? AcademicYear::first();

        if (!$currentYear) {
            $this->command->error('❌ Aucun année académique trouvée. Lancez d\'abord : php artisan db:seed --class=AcademicSeeder');
            return;
        }

        // Créer quelques évaluations de test
        $this->createSampleEvaluations($currentYear);

        // Créer des notes de test pour les élèves inscrits
        $this->createSampleGrades();

        $this->command->info('✅ Module Grades seedé avec succès !');
    }

    private function createSampleEvaluations($academicYear): void
    {
        $this->command->info('📝 Création des évaluations...');

        // Récupérer les données nécessaires
        $teachers = User::whereHas('roles', function($q) {
            $q->where('name', 'teacher');
        })->get();

        $subjects = Subject::all();
        $classes = ClassRoom::all();

        if ($teachers->isEmpty() || $subjects->isEmpty() || $classes->isEmpty()) {
            $this->command->warn('⚠️ Données insuffisantes pour créer des évaluations. Assurez-vous d\'avoir des enseignants, matières et classes.');
            return;
        }

        $evaluationData = [
            [
                'name' => 'Contrôle Continu Mathématiques - Trimestre 1',
                'code' => 'MATH-CC-T1-' . $academicYear->name,
                'description' => 'Premier trimestre - Contrôle des connaissances de base',
                'type' => 'continuous',
                'period' => 'Trimestre 1',
                'coefficient' => 1,
                'weight_percentage' => 30,
                'evaluation_date' => now()->addDays(5),
                'maximum_score' => 20,
                'minimum_score' => 0,
                'subject_name' => 'Mathématiques',
                'class_name' => 'Classe 1A',
                'status' => 'planned',
            ],
            [
                'name' => 'Devoir Surveillé Français - Trimestre 1',
                'code' => 'FRANC-DS-T1-' . $academicYear->name,
                'description' => 'Premier trimestre - Expression écrite',
                'type' => 'semester',
                'period' => 'Trimestre 1',
                'coefficient' => 2,
                'weight_percentage' => 50,
                'evaluation_date' => now()->addDays(10),
                'maximum_score' => 20,
                'minimum_score' => 0,
                'subject_name' => 'Français',
                'class_name' => 'Classe 1A',
                'status' => 'planned',
            ],
            [
                'name' => 'Évaluation Physique-Chimie - Trimestre 1',
                'code' => 'PC-EVAL-T1-' . $academicYear->name,
                'description' => 'Premier trimestre - Lois physiques fondamentales',
                'type' => 'continuous',
                'period' => 'Trimestre 1',
                'coefficient' => 1,
                'weight_percentage' => 25,
                'evaluation_date' => now()->addDays(3),
                'maximum_score' => 20,
                'minimum_score' => 0,
                'subject_name' => 'Physique-Chimie',
                'class_name' => 'Classe 1A',
                'status' => 'ongoing',
            ]
        ];

        $createdEvaluations = [];
        foreach ($evaluationData as $data) {
            // Trouver le sujet
            $subject = Subject::where('name', $data['subject_name'])->first();
            if (!$subject) continue;

            // Trouver la classe
            $class = ClassRoom::where('name', $data['class_name'])->first();
            if (!$class) continue;

            // Trouver un enseignant pour cette matière et classe
            $teacher = TeacherSubject::where('subject_id', $subject->id)
                ->where('academic_year_id', $academicYear->id)
                ->whereHas('classes', function($q) use ($class) {
                    $q->where('id', $class->id);
                })
                ->first()?->teacher ?? $teachers->first();

            if (!$teacher) continue;

            unset($data['subject_name'], $data['class_name']);

            $evaluation = Evaluation::create(array_merge($data, [
                'academic_year_id' => $academicYear->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'uuid' => \Illuminate\Support\Str::uuid()
            ]));

            $createdEvaluations[] = $evaluation;
        }

        $this->command->info("✅ Créé " . count($createdEvaluations) . " évaluations");
    }

    private function createSampleGrades(): void
    {
        $this->command->info('📊 Création des notes...');

        $evaluations = Evaluation::all();
        $students = Student::whereHas('enrollments', function($q) {
            $q->where('status', 'active');
        })->get();

        if ($evaluations->isEmpty() || $students->isEmpty()) {
            $this->command->warn('⚠️ Pas assez de données pour créer des notes de test.');
            return;
        }

        $gradesCreated = 0;

        foreach ($evaluations as $evaluation) {
            // Récupérer les élèves inscrits dans cette classe
            $enrolledStudents = $students->filter(function($student) use ($evaluation) {
                return $student->enrollments()
                    ->where('class_id', $evaluation->class_id)
                    ->where('academic_year_id', $evaluation->academic_year_id)
                    ->where('status', 'active')
                    ->exists();
            });

            foreach ($enrolledStudents as $student) {
                // Générer une note aléatoire mais réaliste
                $isAbsent = rand(1, 10) > 9; // 10% de chances d'absence
                $score = null;

                if (!$isAbsent) {
                    // Distribution normale autour de la moyenne
                    $baseScore = 12 + (rand(-500, 500) / 100); // Entre 7 et 17 environ
                    $score = max(0, min(20, round($baseScore, 2)));
                }

                Grade::create([
                    'student_id' => $student->id,
                    'evaluation_id' => $evaluation->id,
                    'score' => $score,
                    'coefficient' => 1.0,
                    'is_absent' => $isAbsent,
                    'comments' => $isAbsent ? 'Absent(e)' : null,
                    'recorded_by' => 1, // Super admin
                    'recorded_at' => now(),
                    'uuid' => \Illuminate\Support\Str::uuid()
                ]);

                $gradesCreated++;
            }
        }

        $this->command->info("✅ Créé {$gradesCreated} notes");
    }
}
