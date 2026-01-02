<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\User;
use App\Models\MP\StudentMP;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;
use App\Models\MP\ClassMP;
use App\Models\College\ClassCollege;
use App\Models\Lycee\ClassLycee;
use App\Models\MP\EnrollmentMP;
use App\Models\College\EnrollmentCollege;
use App\Models\Lycee\EnrollmentLycee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Direction
 * 
 * Vue consolidée pour la Direction:
 * - Indicateurs clés (effectifs, inscriptions, finance)
 * - Alertes et actions requises
 * - Statistiques globales
 * 
 * Récupère les données en temps réel depuis les 3 bases de données
 */
class DirectionDashboardController extends Controller
{
    /**
     * Obtenir les données du dashboard Direction
     */
    public function index(Request $request)
    {
        try {
            $schoolYear = SchoolYear::current();
            $schoolYearData = $schoolYear ? [
                'id' => $schoolYear->id,
                'name' => $schoolYear->name,
                'is_current' => true,
            ] : null;

            $data = [
                'school_year' => $schoolYearData,
                'overview' => $this->getOverview(),
                'enrollments' => $this->getEnrollmentStats(),
                'classes' => $this->getClassStats(),
                'finance' => $this->getFinanceOverview(),
                'alerts' => $this->getAlerts(),
                'recent_activity' => $this->getRecentActivity(),
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Erreur dashboard direction: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors du chargement du dashboard',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les alertes pour la direction
     */
    private function getAlerts(): array
    {
        $alerts = [];
        $schoolYear = SchoolYear::current();
        if (!$schoolYear) return [];

        // 1. Inscriptions en attente
        $pendingEnrollments = EnrollmentMP::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->count()
            + EnrollmentCollege::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->count()
            + EnrollmentLycee::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->count();

        if ($pendingEnrollments > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'clipboard-list',
                'title' => 'Inscriptions en attente',
                'message' => "Il y a $pendingEnrollments inscriptions à valider.",
                'action' => '/admin/validations',
                'priority' => 'high'
            ];
        }

        // 2. Classes pleines
        $classStats = $this->getClassStats();
        if ($classStats['full'] > 0) {
            $alerts[] = [
                'type' => 'error',
                'icon' => 'users',
                'title' => 'Classes saturées',
                'message' => "{$classStats['full']} classes ont atteint leur capacité maximale.",
                'action' => '/admin/academic',
                'priority' => 'medium'
            ];
        }

        // 3. Impayés critiques (À implémenter réellement plus tard)
        // Pour l'instant on ne retourne rien de faux.

        return $alerts;
    }

    /**
     * Activité récente consolidée (Dernières inscriptions)
     */
    private function getRecentActivity(): array
    {
        $schoolYear = SchoolYear::current();
        if (!$schoolYear) return [];

        $limit = 5;

        // Fetch from all cycles
        $mp = EnrollmentMP::with('student', 'class')->where('school_year_id', $schoolYear->id)->latest()->take($limit)->get();
        $college = EnrollmentCollege::with('student', 'class')->where('school_year_id', $schoolYear->id)->latest()->take($limit)->get();
        $lycee = EnrollmentLycee::with('student', 'class')->where('school_year_id', $schoolYear->id)->latest()->take($limit)->get();

        $activity = [];

        foreach (['mp' => $mp, 'college' => $college, 'lycee' => $lycee] as $cycle => $items) {
            foreach ($items as $item) {
                $activity[] = [
                    'type' => 'enrollment',
                    'message' => "Nouvelle inscription: " . $item->student->nom . " " . $item->student->prenoms,
                    'student_name' => $item->student->nom . " " . $item->student->prenoms,
                    'class_name' => $item->class->nom ?? 'N/A',
                    'date' => $item->created_at->format('Y-m-d H:i:s'),
                    'status' => $item->statut,
                    'cycle' => $cycle
                ];
            }
        }

        // Sort by date desc
        usort($activity, fn($a, $b) => strcmp($b['date'], $a['date']));

        return array_slice($activity, 0, $limit);
    }

    /**
     * Vue d'ensemble globale - Données en temps réel
     */
    private function getOverview(): array
    {
        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return [
                'total_students' => 0,
                'students_mp' => 0,
                'students_main' => 0,
                'students_college' => 0,
                'students_lycee' => 0,
                'total_teachers' => 0,
                'total_parents' => 0,
                'total_staff' => 0,
                'total_classes' => 0,
            ];
        }

        $studentsMP = EnrollmentMP::where('school_year_id', $schoolYear->id)->where('statut', 'validee')->count();
        $studentsCollege = EnrollmentCollege::where('school_year_id', $schoolYear->id)->where('statut', 'validee')->count();
        $studentsLycee = EnrollmentLycee::where('school_year_id', $schoolYear->id)->where('statut', 'validee')->count();
        $totalStudents = $studentsMP + $studentsCollege + $studentsLycee;

        $totalTeachers = User::whereIn('role', ['enseignant', 'teacher'])->where('is_active', true)->count();
        $totalParents = User::where('role', 'parent')->where('is_active', true)->count();
        $totalStaff = User::whereIn('role', ['direction', 'secretariat', 'comptabilite', 'admin', 'super_admin'])->where('is_active', true)->count();

        $classesMP = ClassMP::where('is_active', true)->count();
        $classesCollege = ClassCollege::where('is_active', true)->count();
        $classesLycee = ClassLycee::where('is_active', true)->count();
        $totalClasses = $classesMP + $classesCollege + $classesLycee;

        $today = now()->startOfDay();
        $todayEnrollments = EnrollmentMP::where('created_at', '>=', $today)->count()
            + EnrollmentCollege::where('created_at', '>=', $today)->count()
            + EnrollmentLycee::where('created_at', '>=', $today)->count();

        $todayPayments = \App\Models\Finance\Payment::where('created_at', '>=', $today)->sum('montant');

        return [
            'total_students' => $totalStudents,
            'students_mp' => $studentsMP,
            'students_main' => $studentsMP, // Alias legacy
            'students_college' => $studentsCollege,
            'students_lycee' => $studentsLycee,
            'total_teachers' => $totalTeachers,
            'total_parents' => $totalParents,
            'total_staff' => $totalStaff,
            'total_classes' => $totalClasses,
            'today_enrollments' => $todayEnrollments,
            'today_payments' => (float)$todayPayments,
            'pending_enrollments' => EnrollmentMP::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->count()
                + EnrollmentCollege::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->count()
                + EnrollmentLycee::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->count(),
        ];
    }

    /**
     * Statistiques des inscriptions - Données en temps réel
     */
    private function getEnrollmentStats(): array
    {
        $schoolYear = SchoolYear::current();
        if (!$schoolYear) return ['pending' => 0, 'validated' => 0, 'rejected' => 0, 'total' => 0, 'with_scholarship' => 0];

        $mp = EnrollmentMP::where('school_year_id', $schoolYear->id)->get();
        $college = EnrollmentCollege::where('school_year_id', $schoolYear->id)->get();
        $lycee = EnrollmentLycee::where('school_year_id', $schoolYear->id)->get();

        $all = $mp->concat($college)->concat($lycee);

        return [
            'pending' => $all->where('statut', 'en_attente')->count(),
            'validated' => $all->where('statut', 'validee')->count(),
            'rejected' => $all->where('statut', 'refusee')->count(),
            'total' => $all->count(),
            'with_scholarship' => $all->where('a_bourse', true)->count(),
        ];
    }

    /**
     * Statistiques des classes - Données consolidées
     */
    private function getClassStats(): array
    {
        $mp = ClassMP::where('is_active', true)->get();
        $college = ClassCollege::where('is_active', true)->get();
        $lycee = ClassLycee::where('is_active', true)->get();

        $totalEnrolled = $mp->sum('effectif_actuel') + $college->sum('effectif_actuel') + $lycee->sum('effectif_actuel');
        $totalCapacity = $mp->sum('seuil_maximum') + $college->sum('seuil_maximum') + $lycee->sum('seuil_maximum');

        $totalClasses = $mp->count() + $college->count() + $lycee->count();

        $almostFullCount = 0;
        $fullCount = 0;

        foreach ([$mp, $college, $lycee] as $collection) {
            foreach ($collection as $cls) {
                $max = $cls->seuil_maximum ?: 50; // Fallback if 0
                if ($cls->effectif_actuel >= $max) {
                    $fullCount++;
                } elseif ($cls->effectif_actuel >= ($max * 0.9)) {
                    $almostFullCount++;
                }
            }
        }

        return [
            'total' => $totalClasses,
            'total_capacity' => $totalCapacity,
            'total_enrolled' => $totalEnrolled,
            'fill_rate' => $totalCapacity > 0 ? round(($totalEnrolled / $totalCapacity) * 100, 1) : 0,
            'almost_full' => $almostFullCount,
            'full' => $fullCount,
            'distribution' => $this->getDetailedClassDistribution(),
        ];
    }

    /**
     * Distribution détaillée des classes pour les dashboards
     */
    private function getDetailedClassDistribution(): array
    {
        $limit = 8;
        $mp = ClassMP::where('is_active', true)->take($limit)->get();
        $college = ClassCollege::where('is_active', true)->take($limit)->get();
        $lycee = ClassLycee::where('is_active', true)->take($limit)->get();

        $distribution = [];
        foreach ([$mp, $college, $lycee] as $collection) {
            foreach ($collection as $cls) {
                $distribution[] = [
                    'id' => $cls->id,
                    'name' => $cls->nom,
                    'level' => $cls->niveau,
                    'count' => $cls->effectif_actuel,
                    'capacity' => $cls->seuil_maximum ?: 50,
                ];
            }
        }

        return array_slice($distribution, 0, 12);
    }

    /**
     * Aperçu financier
     */
    private function getFinanceOverview(): array
    {
        $schoolYear = SchoolYear::current();
        if (!$schoolYear) return ['total_expected' => 0, 'total_collected' => 0, 'total_pending' => 0, 'recovery_rate' => 0];

        $mp = EnrollmentMP::where('school_year_id', $schoolYear->id)->get();
        $college = EnrollmentCollege::where('school_year_id', $schoolYear->id)->get();
        $lycee = EnrollmentLycee::where('school_year_id', $schoolYear->id)->get();

        $all = $mp->concat($college)->concat($lycee);

        $totalExpected = $all->sum('montant_final');
        $totalCollected = $all->sum(fn($enrollment) => $enrollment->montant_paye ?? 0);
        $totalPending = max(0, $totalExpected - $totalCollected);

        return [
            'total_expected' => (float)$totalExpected,
            'total_collected' => (float)$totalCollected,
            'total_pending' => (float)$totalPending,
            'recovery_rate' => $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 1) : 0,
            'total_scholarships' => (float)$all->sum('montant_bourse'),
        ];
    }
    /**
     * Liste consolidée de tous les élèves (MP, Collège, Lycée)
     */
    public function getAllStudents()
    {
        $schoolYear = SchoolYear::current();

        if (!$schoolYear) {
            return response()->json(['data' => []]);
        }

        // MP
        $studentsMP = StudentMP::with(['enrollments' => function ($q) use ($schoolYear) {
            $q->where('school_year_id', $schoolYear->id)->with('class');
        }, 'guardians'])
            ->where('is_active', true)
            ->get()
            ->map(fn($s) => $this->formatStudentForList($s, 'mp'));

        // College
        $studentsCollege = StudentCollege::with(['enrollments' => function ($q) use ($schoolYear) {
            $q->where('school_year_id', $schoolYear->id)->with('class');
        }, 'guardians'])
            ->where('is_active', true)
            ->get()
            ->map(fn($s) => $this->formatStudentForList($s, 'college'));

        // Lycee
        $studentsLycee = StudentLycee::with(['enrollments' => function ($q) use ($schoolYear) {
            $q->where('school_year_id', $schoolYear->id)->with('class');
        }, 'guardians'])
            ->where('is_active', true)
            ->get()
            ->map(fn($s) => $this->formatStudentForList($s, 'lycee'));

        $all = $studentsMP->concat($studentsCollege)->concat($studentsLycee);

        return response()->json(['data' => $all->values()]);
    }

    /**
     * Obtenir les validations en attente
     */
    public function getPendingValidations()
    {
        $schoolYear = SchoolYear::current();
        if (!$schoolYear) return response()->json(['data' => []]);

        $mp = EnrollmentMP::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->with(['student', 'class'])->get();
        $college = EnrollmentCollege::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->with(['student', 'class'])->get();
        $lycee = EnrollmentLycee::where('school_year_id', $schoolYear->id)->where('statut', 'en_attente')->with(['student', 'class'])->get();

        $validations = [];

        foreach (['mp' => $mp, 'college' => $college, 'lycee' => $lycee] as $cycle => $items) {
            foreach ($items as $item) {
                $validations[] = [
                    'id' => $item->id,
                    'type' => 'inscription',
                    'title' => 'Inscription ' . $item->student->nom . ' ' . $item->student->prenoms,
                    'details' => 'Classe: ' . ($item->class->nom ?? 'N/A') . " (" . strtoupper($cycle) . ")",
                    'date' => $item->created_at->format('d/m/Y'),
                    'submittedBy' => 'Secrétariat / Parent',
                    'cycle' => $cycle,
                    '_raw' => $item
                ];
            }
        }

        return response()->json(['data' => $validations]);
    }

    /**
     * Valider ou rejeter un élément
     */
    public function updateValidation(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:validee,refusee',
            'cycle' => 'required|in:mp,college,lycee',
            'type' => 'required|string'
        ]);

        $status = $request->status;
        $cycle = $request->cycle;

        if ($request->type === 'inscription') {
            $model = match ($cycle) {
                'mp' => EnrollmentMP::class,
                'college' => EnrollmentCollege::class,
                'lycee' => EnrollmentLycee::class
            };

            $enrollment = $model::findOrFail($id);
            $enrollment->update(['statut' => $status]);

            return response()->json(['message' => 'Statut mis à jour avec succès']);
        }

        return response()->json(['error' => 'Type non supporté'], 400);
    }

    private function formatStudentForList($student, $cycle)
    {
        $enrollment = $student->enrollments->first();
        $parent = $student->guardians->first(); // Père ou mère

        return [
            'id' => $student->id,
            'matricule' => $student->matricule,
            'firstName' => $student->prenoms,
            'lastName' => $student->nom,
            'gender' => $student->sexe,
            'dateOfBirth' => $student->date_naissance, // Cast to string handled by Laravel
            'currentClass' => $enrollment?->class?->nom ?? 'Non inscrit',
            'classId' => $enrollment?->class_id,
            'parentName' => $parent?->nom_complet ?? '-',
            'parentPhone' => $parent?->telephone_1 ?? '-',
            'status' => 'active', // TODO: real status
            'photo' => $student->photo_url ?? '', // Acessor needed?
            'cycle' => $cycle
        ];
    }
}
