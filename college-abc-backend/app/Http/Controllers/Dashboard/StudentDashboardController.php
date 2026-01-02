<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MP\StudentMP;
use App\Models\MP\GradeMP;
use App\Models\MP\ReportCardMP;
use App\Models\SchoolYear;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;

/**
 * Dashboard Élève
 * 
 * Vue pour les élèves:
 * - Leurs notes et bulletins
 * - Emploi du temps
 * - Devoirs
 * - Assiduité
 */
class StudentDashboardController extends Controller
{
    /**
     * Dashboard principal élève
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolYear = SchoolYear::current();

        // Récupérer l'élève associé à l'utilisateur
        $student = $this->getStudent($user->id);

        if (!$student) {
            return response()->json([
                'message' => 'Aucun profil élève associé à ce compte.',
            ], 404);
        }

        return response()->json([
            'student' => $this->formatStudent($student),
            'school_year' => $schoolYear?->name,
            'grades_summary' => $this->getGradesSummary($student, $schoolYear),
            'recent_grades' => $this->getRecentGrades($student, $schoolYear),
            'attendance_summary' => $this->getAttendanceSummary($student, $schoolYear),
            'upcoming_homework' => $this->getUpcomingHomework($student),
            'schedule_today' => $this->getTodaySchedule($student),
            'announcements' => $this->getAnnouncements(),
        ]);
    }

    /**
     * Récupérer l'élève correspondant à l'utilisateur
     * Scanne les 3 bases de données (MP, Collège, Lycée)
     */
    private function getStudent(string $userId)
    {
        // 1. Recherche dans Maternelle/Primaire
        $student = StudentMP::where('user_id', $userId)->where('is_active', true)->first();
        if ($student) return $student;

        // 2. Recherche dans Collège
        $student = StudentCollege::where('user_id', $userId)->where('is_active', true)->first();
        if ($student) return $student;

        // 3. Recherche dans Lycée
        $student = StudentLycee::where('user_id', $userId)->where('is_active', true)->first();
        if ($student) return $student;

        return null;
    }

    /**
     * Formater les infos de l'élève
     */
    private function formatStudent($student): array
    {
        $enrollment = $student->currentEnrollment();

        return [
            'id' => $student->id,
            'matricule' => $student->matricule,
            'nom' => $student->nom,
            'prenoms' => $student->prenoms,
            'full_name' => $student->full_name,
            'photo_url' => $student->photo_url,
            'class_name' => $enrollment?->class?->full_name ?? '-',
            'niveau' => $enrollment?->class?->niveau ?? '-',
        ];
    }

    /**
     * Résumé des notes
     */
    private function getGradesSummary($student, ?SchoolYear $schoolYear): array
    {
        if (!$schoolYear) {
            return ['moyenne_generale' => 0, 'rang' => 0, 'effectif' => 0, 'trimestre' => 0];
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return ['moyenne_generale' => 0, 'rang' => 0, 'effectif' => 0, 'trimestre' => 0];
        }

        $currentTrimestre = $this->getCurrentTrimestre();

        // Chercher le bulletin publié
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

        // Calculer à partir des notes publiées
        $grades = GradeMP::where('student_id', $student->id)
            ->where('class_id', $enrollment->class_id)
            ->where('school_year_id', $schoolYear->id)
            ->where('trimestre', $currentTrimestre)
            ->where('is_published', true)
            ->get();

        return [
            'moyenne_generale' => round($grades->avg('note_sur_20') ?? 0, 2),
            'rang' => 0,
            'effectif' => $enrollment->class->effectif_actuel ?? 0,
            'trimestre' => $currentTrimestre,
        ];
    }

    /**
     * Notes récentes (publiées uniquement)
     */
    private function getRecentGrades($student, ?SchoolYear $schoolYear): array
    {
        if (!$schoolYear) return [];

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) return [];

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
                'commentaire' => $g->commentaire,
            ])
            ->toArray();
    }

    /**
     * Résumé assiduité
     */
    private function getAttendanceSummary($student, ?SchoolYear $schoolYear): array
    {
        if (!$schoolYear) {
            return ['absences' => 0, 'retards' => 0, 'heures_manquees' => 0];
        }

        // Compter les absences (simplifié)
        $absences = DB::connection('school_mp')
            ->table('attendances_mp')
            ->where('student_id', $student->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('type', 'absence')
            ->count();

        $retards = DB::connection('school_mp')
            ->table('attendances_mp')
            ->where('student_id', $student->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('type', 'retard')
            ->count();

        // Calculer les heures (estimation: 2h par absence)
        $heuresManquees = ($absences * 2) + ($retards * 0.5);

        return [
            'absences' => $absences,
            'retards' => $retards,
            'heures_manquees' => round($heuresManquees, 1),
        ];
    }

    /**
     * Devoirs à venir
     */
    private function getUpcomingHomework($student): array
    {
        // TODO: Implémenter avec une vraie table devoirs
        // Retourne vide pour l'instant (plus de mocks)
        return [];
    }

    /**
     * Emploi du temps du jour
     */
    private function getTodaySchedule($student): array
    {
        // TODO: Implémenter avec vraie table emploi du temps
        // Retourne vide pour l'instant (plus de mocks)
        return [];
    }

    /**
     * Annonces récentes
     */
    private function getAnnouncements(): array
    {
        return Notification::where('type', 'announcement')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(fn($n) => [
                'title' => $n->title,
                'content' => $n->message,
                'date' => $n->created_at->toDateString(),
            ])
            ->toArray();
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
     * Notes détaillées de l'élève
     */
    public function grades(Request $request)
    {
        $user = $request->user();
        $schoolYear = SchoolYear::current();
        $student = $this->getStudent($user->id);

        if (!$student) {
            return response()->json(['message' => 'Profil élève non trouvé.'], 404);
        }

        $trimestre = $request->get('trimestre', $this->getCurrentTrimestre());
        $enrollment = $student->currentEnrollment();

        if (!$enrollment) {
            return response()->json(['grades' => [], 'average' => 0]);
        }

        $grades = GradeMP::where('student_id', $student->id)
            ->where('class_id', $enrollment->class_id)
            ->where('school_year_id', $schoolYear->id)
            ->where('trimestre', $trimestre)
            ->where('is_published', true)
            ->with('subject:id,nom,code,coefficient')
            ->get();

        // Grouper par matière
        $bySubject = $grades->groupBy('subject_id')->map(function ($subjectGrades) {
            $subject = $subjectGrades->first()->subject;
            return [
                'subject' => $subject->only(['id', 'nom', 'code', 'coefficient']),
                'grades' => $subjectGrades->map(fn($g) => [
                    'note' => $g->note_sur_20,
                    'type' => $g->type_evaluation,
                    'date' => $g->date_evaluation->toDateString(),
                    'commentaire' => $g->commentaire,
                ]),
                'moyenne' => round($subjectGrades->avg('note_sur_20'), 2),
            ];
        })->values();

        return response()->json([
            'trimestre' => $trimestre,
            'by_subject' => $bySubject,
            'average' => round($grades->avg('note_sur_20') ?? 0, 2),
        ]);
    }

    /**
     * Bulletins de l'élève
     */
    public function bulletins(Request $request)
    {
        $user = $request->user();
        $student = $this->getStudent($user->id);

        if (!$student) {
            return response()->json(['message' => 'Profil élève non trouvé.'], 404);
        }

        $bulletins = ReportCardMP::where('student_id', $student->id)
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
            ]);

        return response()->json(['bulletins' => $bulletins]);
    }
}
