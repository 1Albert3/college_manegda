<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MP\StudentMP;
use App\Models\MP\GradeMP;
use App\Models\MP\EnrollmentMP;
use App\Models\MP\AttendanceMP;
use App\Models\MP\ReportCardMP;
use App\Models\SchoolYear;
use App\Models\Notification;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Parents
 * 
 * Vue pour les parents:
 * - Informations sur leurs enfants
 * - Notes récentes
 * - Absences
 * - Situation financière
 */
class ParentDashboardController extends Controller
{
    /**
     * Dashboard principal des parents
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolYear = SchoolYear::current();

        // Récupérer les enfants du parent
        $children = $this->getChildren($user->id);

        if ($children->isEmpty()) {
            return response()->json([
                'message' => 'Aucun enfant associé à ce compte.',
                'children' => [],
            ], 200);
        }

        // Enfant sélectionné (ou premier par défaut)
        $childId = $request->get('child_id');
        $currentChild = $childId
            ? $children->firstWhere('id', $childId)
            : $children->first();

        if (!$currentChild) {
            $currentChild = $children->first();
        }

        return response()->json([
            'children' => $children->map(fn($c) => $this->formatChild($c)),
            'current_child' => $this->formatChild($currentChild),
            'grades_summary' => $this->getGradesSummary($currentChild, $schoolYear),
            'recent_grades' => $this->getRecentGrades($currentChild, $schoolYear),
            'upcoming_events' => $this->getUpcomingEvents($currentChild),
            'attendance_summary' => $this->getAttendanceSummary($currentChild, $schoolYear),
            'payment_status' => $this->getPaymentStatus($currentChild, $schoolYear),
            'unread_messages' => $this->getUnreadMessagesCount($user->id),
        ]);
    }

    /**
     * Récupérer les enfants d'un parent
     */
    private function getChildren(string $parentUserId)
    {
        // 1. MP
        $childrenMP = StudentMP::whereHas('guardians', function ($q) use ($parentUserId) {
            $q->where('user_id', $parentUserId);
        })->where('is_active', true)->with(['enrollments.class', 'guardians'])->get();

        // 2. Collège
        $childrenCollege = StudentCollege::whereHas('guardians', function ($q) use ($parentUserId) {
            $q->where('user_id', $parentUserId);
        })->where('is_active', true)->get();

        // 3. Lycée
        $childrenLycee = StudentLycee::whereHas('guardians', function ($q) use ($parentUserId) {
            $q->where('user_id', $parentUserId);
        })->where('is_active', true)->get();

        return $childrenMP->concat($childrenCollege)->concat($childrenLycee);
    }

    /**
     * Formater les données d'un enfant
     */
    private function formatChild($student): array
    {
        $enrollment = $student->currentEnrollment();

        // Gestion des noms de classes différents selon les cycles
        $className = '-';
        $niveau = '-';

        if ($enrollment && $enrollment->class) {
            $className = $enrollment->class->full_name ?? $enrollment->class->nom ?? '-';
            $niveau = $enrollment->class->niveau ?? '-';
        }

        return [
            'id' => $student->id,
            'matricule' => $student->matricule,
            'nom' => $student->nom,
            'prenoms' => $student->prenoms,
            'full_name' => $student->full_name ?? ($student->nom . ' ' . $student->prenoms),
            'photo_url' => $student->photo_url,
            'class_name' => $className,
            'niveau' => $niveau,
            // Ajout du type pour le frontend pour savoir quelle API appeler pour les détails
            'cycle' => $this->getCycleFromStudent($student),
        ];
    }

    private function getCycleFromStudent($student): string
    {
        if ($student instanceof StudentMP) return 'mp';
        if ($student instanceof StudentCollege) return 'college';
        if ($student instanceof StudentLycee) return 'lycee';
        return 'unknown';
    }

    /**
     * Résumé des notes pour un trimestre
     */
    private function getGradesSummary($student, ?SchoolYear $schoolYear): array
    {
        // TODO: Implémenter pour Collège et Lycée
        if (!($student instanceof StudentMP)) {
            return ['moyenne_generale' => 0, 'rang' => 0, 'effectif' => 0, 'trimestre' => 0];
        }

        if (!$schoolYear) {
            return ['moyenne_generale' => 0, 'rang' => 0, 'effectif' => 0, 'trimestre' => 0];
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return ['moyenne_generale' => 0, 'rang' => 0, 'effectif' => 0, 'trimestre' => 0];
        }

        // Déterminer le trimestre actuel
        $currentTrimestre = $this->getCurrentTrimestre();

        // Récupérer le bulletin si disponible
        $reportCard = ReportCardMP::where('student_id', $student->id)
            ->where('class_id', $enrollment->class_id)
            ->where('school_year_id', $schoolYear->id)
            ->where('trimestre', $currentTrimestre)
            ->where('is_validated', true)
            ->first();

        if ($reportCard) {
            return [
                'moyenne_generale' => $reportCard->moyenne_generale,
                'rang' => $reportCard->rang,
                'effectif' => $reportCard->effectif_classe,
                'trimestre' => $currentTrimestre,
            ];
        }

        // Calculer à partir des notes si pas de bulletin
        $grades = GradeMP::where('student_id', $student->id)
            ->where('class_id', $enrollment->class_id)
            ->where('school_year_id', $schoolYear->id)
            ->where('trimestre', $currentTrimestre)
            ->where('is_published', true)
            ->get();

        $moyenne = $grades->avg('note_sur_20') ?? 0;

        return [
            'moyenne_generale' => round($moyenne, 2),
            'rang' => 0, // Non calculé sans bulletin
            'effectif' => $enrollment->class->effectif_actuel ?? 0,
            'trimestre' => $currentTrimestre,
        ];
    }

    /**
     * Notes récentes (5 dernières)
     */
    private function getRecentGrades($student, ?SchoolYear $schoolYear): array
    {
        // TODO: Implémenter pour Collège et Lycée
        if (!($student instanceof StudentMP)) {
            return [];
        }

        if (!$schoolYear) {
            return [];
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return [];
        }

        return GradeMP::where('student_id', $student->id)
            ->where('class_id', $enrollment->class_id)
            ->where('school_year_id', $schoolYear->id)
            ->where('is_published', true)
            ->with('subject:id,nom,code')
            ->orderByDesc('date_evaluation')
            ->limit(5)
            ->get()
            ->map(fn($g) => [
                'subject' => $g->subject?->nom ?? 'N/A',
                'note' => $g->note_sur_20,
                'date' => $g->date_evaluation->toDateString(),
                'type' => $g->type_evaluation,
            ])
            ->toArray();
    }

    /**
     * Événements à venir
     */
    private function getUpcomingEvents($student): array
    {
        // TODO: Implémenter avec un vrai système d'événements
        // Retourne vide pour l'instant (plus de mocks)
        return [];
    }

    /**
     * Résumé des absences
     */
    private function getAttendanceSummary($student, ?SchoolYear $schoolYear): array
    {
        // TODO: Implémenter pour Collège et Lycée
        if (!($student instanceof StudentMP)) {
            return ['absences' => 0, 'retards' => 0, 'non_justifiees' => 0];
        }

        if (!$schoolYear) {
            return ['absences' => 0, 'retards' => 0, 'non_justifiees' => 0];
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return ['absences' => 0, 'retards' => 0, 'non_justifiees' => 0];
        }

        // Base query
        $query = DB::connection('school_mp')
            ->table('attendances_mp')
            ->where('student_id', $student->id);

        if ($schoolYear && $schoolYear->start_date && $schoolYear->end_date) {
            $query->whereBetween('date', [$schoolYear->start_date, $schoolYear->end_date]);
        }

        $absences = (clone $query)->where('type', 'absence')->count();

        $retards = (clone $query)->where('type', 'retard')->count();

        $nonJustifiees = (clone $query)
            ->whereIn('statut', ['non_justifiee', 'en_attente']) // Inclure en attente comme non justifiée pour alerte
            ->count();

        return [
            'absences' => $absences,
            'retards' => $retards,
            'non_justifiees' => $nonJustifiees,
        ];
    }

    /**
     * Situation financière
     */
    private function getPaymentStatus($student, ?SchoolYear $schoolYear): array
    {
        if (!$schoolYear) {
            return ['total' => 0, 'paid' => 0, 'remaining' => 0, 'next_deadline' => null];
        }

        // Attention: Pour le collège/lycée, l'enrollment peut être différent
        $enrollment = $student->enrollments()
            ->where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->first();

        if (!$enrollment) {
            return ['total' => 0, 'paid' => 0, 'remaining' => 0, 'next_deadline' => null];
        }

        return [
            'total' => $enrollment->montant_final,
            'paid' => $enrollment->montant_paye ?? 0,
            'remaining' => $enrollment->solde_restant ?? $enrollment->montant_final,
            'next_deadline' => $enrollment->prochaine_echeance ?? null,
        ];
    }

    /**
     * Nombre de messages non lus
     */
    private function getUnreadMessagesCount(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Déterminer le trimestre actuel basé sur la date
     */
    private function getCurrentTrimestre(): int
    {
        $month = now()->month;

        if ($month >= 10 || $month <= 12) {
            return 1; // Octobre - Décembre
        } elseif ($month >= 1 && $month <= 3) {
            return 2; // Janvier - Mars
        } else {
            return 3; // Avril - Juin
        }
    }

    /**
     * Notes d'un enfant spécifique
     */
    public function childGrades(Request $request, string $childId)
    {
        $user = $request->user();
        $schoolYear = SchoolYear::current();

        // 1. Chercher dans toutes les tables
        $child = $this->findChildById($childId, $user->id);

        if (!$child) {
            return response()->json(['message' => 'Enfant non trouvé.'], 404);
        }

        // TODO: Implémenter logique spécifique par cycle
        if (!($child instanceof StudentMP)) {
            return response()->json([
                'child' => $this->formatChild($child),
                'trimestre' => $this->getCurrentTrimestre(),
                'by_subject' => [],
                'average' => 0,
                'message' => 'Détails non disponibles pour ce cycle pour le moment.'
            ]);
        }

        $trimestre = $request->get('trimestre', $this->getCurrentTrimestre());

        $enrollment = $child->currentEnrollment();
        if (!$enrollment) {
            return response()->json(['grades' => [], 'average' => 0]);
        }

        $grades = GradeMP::where('student_id', $child->id)
            ->where('class_id', $enrollment->class_id)
            ->where('school_year_id', $schoolYear->id)
            ->where('trimestre', $trimestre)
            ->where('is_published', true)
            ->with('subject:id,nom,code,coefficient')
            ->orderBy('subject_id')
            ->orderByDesc('date_evaluation')
            ->get();

        // Grouper par matière
        $bySubject = $grades->groupBy('subject_id')->map(function ($subjectGrades) {
            $subject = $subjectGrades->first()->subject;
            return [
                'subject' => [
                    'id' => $subject->id,
                    'nom' => $subject->nom,
                    'code' => $subject->code,
                    'coefficient' => $subject->coefficient,
                ],
                'grades' => $subjectGrades->map(fn($g) => [
                    'id' => $g->id,
                    'note' => $g->note_sur_20,
                    'type' => $g->type_evaluation,
                    'date' => $g->date_evaluation->toDateString(),
                    'commentaire' => $g->commentaire,
                ]),
                'moyenne' => round($subjectGrades->avg('note_sur_20'), 2),
            ];
        })->values();

        return response()->json([
            'child' => $this->formatChild($child),
            'trimestre' => $trimestre,
            'by_subject' => $bySubject,
            'average' => round($grades->avg('note_sur_20') ?? 0, 2),
        ]);
    }

    /**
     * Bulletins d'un enfant
     */
    public function childBulletins(Request $request, string $childId)
    {
        $user = $request->user();
        $schoolYear = SchoolYear::current();

        $child = $this->findChildById($childId, $user->id);

        if (!$child) {
            return response()->json(['message' => 'Enfant non trouvé.'], 404);
        }

        // TODO: Implémenter logique spécifique par cycle
        if (!($child instanceof StudentMP)) {
            return response()->json([
                'child' => $this->formatChild($child),
                'bulletins' => [],
                'message' => 'Bulletins non disponibles pour ce cycle pour le moment.'
            ]);
        }

        $bulletins = ReportCardMP::where('student_id', $child->id)
            ->where('is_validated', true)
            ->with(['class:id,niveau,nom', 'schoolYear:id,name'])
            ->orderByDesc('school_year_id')
            ->orderByDesc('trimestre')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'trimestre' => $b->trimestre,
                'school_year' => $b->schoolYear->name,
                'class' => $b->class->full_name,
                'moyenne_generale' => $b->moyenne_generale,
                'rang' => $b->rang,
                'effectif' => $b->effectif_classe,
                'mention' => $b->mention,
                'decision' => $b->decision,
                'pdf_url' => $b->pdf_path ? asset('storage/' . $b->pdf_path) : null,
                'published_at' => $b->published_at?->toDateTimeString(),
            ]);

        return response()->json([
            'child' => $this->formatChild($child),
            'bulletins' => $bulletins,
        ]);
    }

    /**
     * Helper pour trouver un enfant par ID dans n'importe quel cycle
     */
    private function findChildById(string $childId, string $parentUserId)
    {
        // 1. MP
        $child = StudentMP::where('id', $childId)->whereHas('guardians', function ($q) use ($parentUserId) {
            $q->where('user_id', $parentUserId);
        })->first();
        if ($child) return $child;

        // 2. Collège
        $child = StudentCollege::where('id', $childId)->whereHas('guardians', function ($q) use ($parentUserId) {
            $q->where('user_id', $parentUserId);
        })->first();
        if ($child) return $child;

        // 3. Lycée
        $child = StudentLycee::where('id', $childId)->whereHas('guardians', function ($q) use ($parentUserId) {
            $q->where('user_id', $parentUserId);
        })->first();
        if ($child) return $child;

        return null;
    }
}
