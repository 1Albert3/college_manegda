<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MP\ClassMP;
use App\Models\MP\GradeMP;
use App\Models\MP\SubjectMP;
use App\Models\College\ClassCollege;
use App\Models\College\GradeCollege;
use App\Models\College\SubjectCollege;
use App\Models\Lycee\ClassLycee;
use App\Models\Lycee\GradeLycee;
use App\Models\Lycee\SubjectLycee;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Enseignant
 * 
 * Vue pour les enseignants:
 * - Classes assignées
 * - Saisie des notes en attente
 * - Statistiques par classe
 */
class TeacherDashboardController extends Controller
{
    /**
     * Dashboard principal enseignant
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolYear = SchoolYear::current();

        // Récupérer les classes de l'enseignant (tous cycles confondus)
        $classes = $this->getTeacherClasses($user->id, $schoolYear);
        $subjects = $this->getTeacherSubjects($user->id);

        return response()->json([
            'teacher' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
            ],
            'school_year' => $schoolYear?->name,
            'current_trimestre' => $this->getCurrentTrimestre(),
            'classes' => $classes,
            'subjects' => $subjects,
            'pending_grades' => $this->getPendingGrades($user->id, $schoolYear),
            'today_schedule' => $this->getTodaySchedule($user->id),
            'recent_activity' => $this->getRecentActivity($user->id),
            'stats' => $this->getTeacherStats($user->id, $schoolYear),
        ]);
    }

    /**
     * Classes assignées à l'enseignant
     */
    private function getTeacherClasses(string $userId, ?SchoolYear $schoolYear): array
    {
        if (!$schoolYear) return [];

        $classes = [];

        // 1. MP - teacher_id in classes_mp references users.id directly
        $classesMP = ClassMP::where('teacher_id', $userId)
            ->where('school_year_id', $schoolYear->id)
            ->where('is_active', true)
            ->get()
            ->map(fn($c) => $this->formatClass($c, 'mp'));

        // 2. Collège - prof_principal_id references teachers_college.id
        // First find the teacher profile
        $teacherCollege = \App\Models\College\TeacherCollege::where('user_id', $userId)->first();
        $classesCollege = collect([]);
        if ($teacherCollege) {
            $classesCollege = ClassCollege::where('prof_principal_id', $teacherCollege->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('is_active', true)
                ->get()
                ->map(fn($c) => $this->formatClass($c, 'college'));
        }


        // 3. Lycée
        $teacherLycee = \App\Models\Lycee\TeacherLycee::where('user_id', $userId)->first();
        $classesLycee = collect([]);
        if ($teacherLycee) {
            // A. Classes dont il est Prof Principal
            $ppClasses = ClassLycee::where('prof_principal_id', $teacherLycee->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('is_active', true)
                ->get()
                ->map(fn($c) => $this->formatClass($c, 'lycee', true));

            // B. Classes où il enseigne une matière (via teacher_subject_assignments)
            // On récupère les IDs uniques des classes
            $assignedClassIds = DB::connection('school_lycee')
                ->table('teacher_subject_assignments')
                ->where('teacher_id', $teacherLycee->id)
                ->where('school_year_id', $schoolYear->id)
                ->pluck('class_id')
                ->unique();

            $regularClasses = ClassLycee::whereIn('id', $assignedClassIds)
                ->where('is_active', true)
                ->get()
                ->map(fn($c) => $this->formatClass($c, 'lycee', false));

            // Fusionner et dédoubler (si PP et Prof simple)
            $classesLycee = $ppClasses->concat($regularClasses)->unique('id');
        }

        return $classesMP->concat($classesCollege)->concat($classesLycee)->values()->toArray();
    }

    private function formatClass($class, string $cycle, bool $isPrincipal = false): array
    {
        return [
            'id' => $class->id,
            'niveau' => $class->niveau,
            'nom' => $class->nom,
            'full_name' => $class->full_name ?? ($class->niveau . ' ' . $class->nom),
            'effectif' => $class->effectif_actuel,
            'is_principal' => $isPrincipal,
            'cycle' => $cycle,
        ];
    }

    /**
     * Matières enseignées
     */
    private function getTeacherSubjects(string $userId): array
    {
        // TODO: Implémenter la logique d'assignation réelle quand la table sera peuplée pour tous les cycles
        return [];
    }

    /**
     * Notes en attente de saisie
     */
    private function getPendingGrades(string $userId, ?SchoolYear $schoolYear): array
    {
        if (!$schoolYear) return [];

        // Pour l'instant, on retourne vide pour éviter d'afficher des données incorrectes
        // La logique sera réactivée quand le système de notes sera pleinement opérationnel
        return [];
    }

    /**
     * Emploi du temps du jour
     */
    private function getTodaySchedule(string $userId): array
    {
        // Retourne vide pour l'instant (pas de mocks)
        // À connecter au module Emploi du temps quand il sera prêt
        return [];
    }

    /**
     * Activité récente
     */
    private function getRecentActivity(string $userId): array
    {
        // Retourne vide pour l'instant (pas de mocks)
        return [];
    }

    /**
     * Statistiques de l'enseignant
     */
    private function getTeacherStats(string $userId, ?SchoolYear $schoolYear): array
    {
        if (!$schoolYear) {
            return ['total_students' => 0, 'total_grades' => 0, 'classes_count' => 0];
        }

        // MP Stats
        $classIdsMP = ClassMP::where('teacher_id', $userId)->where('school_year_id', $schoolYear->id)->pluck('id');
        $totalStudents = ClassMP::whereIn('id', $classIdsMP)->sum('effectif_actuel');
        $totalGrades = GradeMP::where('recorded_by', $userId)->where('school_year_id', $schoolYear->id)->count();

        // Collège Stats
        $teacherCollege = \App\Models\College\TeacherCollege::where('user_id', $userId)->first();
        $classIdsCol = collect([]);
        if ($teacherCollege) {
            $classIdsCol = ClassCollege::where('prof_principal_id', $teacherCollege->id)->where('school_year_id', $schoolYear->id)->pluck('id');
            // Note: For College we should also count assigned classes not just PP, but focusing on Lycee for now as requested.
            $totalStudents += ClassCollege::whereIn('id', $classIdsCol)->sum('effectif_actuel');
            $totalGrades += GradeCollege::where('recorded_by', $userId)->where('school_year_id', $schoolYear->id)->count();
        }

        // Lycée Stats
        $teacherLycee = \App\Models\Lycee\TeacherLycee::where('user_id', $userId)->first();
        $classIdsLycee = collect([]);
        if ($teacherLycee) {
            // Count classes via assignments (more accurate)
            $assignedClassIds = DB::connection('school_lycee')
                ->table('teacher_subject_assignments')
                ->where('teacher_id', $teacherLycee->id)
                ->where('school_year_id', $schoolYear->id)
                ->pluck('class_id')
                ->unique();

            $ppClassIds = ClassLycee::where('prof_principal_id', $teacherLycee->id)
                ->where('school_year_id', $schoolYear->id)
                ->pluck('id');

            $classIdsLycee = $assignedClassIds->merge($ppClassIds)->unique();

            $totalStudents += ClassLycee::whereIn('id', $classIdsLycee)->sum('effectif_actuel');
            $totalGrades += GradeLycee::where('recorded_by', $userId)->where('school_year_id', $schoolYear->id)->count();
        }

        return [
            'total_students' => $totalStudents,
            'total_grades' => $totalGrades,
            'classes_count' => $classIdsMP->count() + $classIdsCol->count() + $classIdsLycee->count(),
        ];
    }

    /**
     * Trimestre actuel
     */
    private function getCurrentTrimestre(): int
    {
        $month = now()->month;
        if ($month >= 10 || $month <= 12) return 1;
        if ($month >= 1 && $month <= 3) return 2;
        return 3;
    }

    /**
     * Statistiques d'une classe (dispatching par cycle)
     */
    public function classStats(Request $request, string $classId)
    {
        $cycle = $request->get('cycle', 'mp');

        if ($cycle === 'college') {
            // TODO: Stats Collège
            return response()->json(['message' => 'Stats Collège TODO']);
        }

        // Default MP
        return $this->classStatsMP($request, $classId);
    }

    private function classStatsMP(Request $request, string $classId)
    {
        $schoolYear = SchoolYear::current();
        $trimestre = $request->get('trimestre', $this->getCurrentTrimestre());

        $class = ClassMP::findOrFail($classId);

        // Moyennes par matière
        $subjectStats = GradeMP::where('class_id', $classId)
            ->where('school_year_id', $schoolYear->id)
            ->where('trimestre', $trimestre)
            ->select(
                'subject_id',
                DB::raw('AVG(note_sur_20) as moyenne'),
                DB::raw('MIN(note_sur_20) as min'),
                DB::raw('MAX(note_sur_20) as max'),
                DB::raw('COUNT(DISTINCT student_id) as nb_eleves')
            )
            ->groupBy('subject_id')
            ->with('subject:id,nom,code')
            ->get();

        // Moyenne générale de la classe
        $classeAverage = GradeMP::where('class_id', $classId)
            ->where('school_year_id', $schoolYear->id)
            ->where('trimestre', $trimestre)
            ->avg('note_sur_20');

        $distribution = [
            'excellent' => GradeMP::where('class_id', $classId)->where('school_year_id', $schoolYear->id)->where('trimestre', $trimestre)->where('note_sur_20', '>=', 16)->count(),
            'bien' => GradeMP::where('class_id', $classId)->where('school_year_id', $schoolYear->id)->where('trimestre', $trimestre)->whereBetween('note_sur_20', [12, 16])->count(),
            'passable' => GradeMP::where('class_id', $classId)->where('school_year_id', $schoolYear->id)->where('trimestre', $trimestre)->whereBetween('note_sur_20', [10, 12])->count(),
            'insuffisant' => GradeMP::where('class_id', $classId)->where('school_year_id', $schoolYear->id)->where('trimestre', $trimestre)->where('note_sur_20', '<', 10)->count(),
        ];

        return response()->json([
            'class' => $class->only(['id', 'niveau', 'nom', 'effectif_actuel']),
            'trimestre' => $trimestre,
            'moyenne_classe' => round($classeAverage ?? 0, 2),
            'by_subject' => $subjectStats,
            'distribution' => $distribution,
        ]);
    }
}
