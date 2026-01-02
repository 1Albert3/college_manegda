<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\College\ReportCardCollege;
use App\Models\College\GradeCollege;
use App\Models\College\StudentCollege;
use App\Models\College\ClassCollege;
use App\Models\SchoolYear;
use App\Services\College\ReportCardCollegeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Contrôleur des Bulletins Collège
 */
class ReportCardCollegeController extends Controller
{
    protected ReportCardCollegeService $reportCardService;

    public function __construct(ReportCardCollegeService $reportCardService)
    {
        $this->reportCardService = $reportCardService;
    }

    /**
     * Liste des bulletins
     */
    public function index(Request $request)
    {
        $query = ReportCardCollege::with(['student', 'class', 'schoolYear']);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('trimestre')) {
            $query->where('trimestre', $request->trimestre);
        }

        $reportCards = $query->orderByDesc('moyenne_generale')->paginate($request->per_page ?? 50);

        return response()->json($reportCards);
    }

    /**
     * Prévisualisation des bulletins d'une classe
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid|exists:school_college.classes_college,id',
            'trimestre' => 'required|in:1,2,3',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $class = ClassCollege::findOrFail($validated['class_id']);

        // Récupérer les élèves via les inscriptions (Enrollments)
        $students = StudentCollege::whereHas('enrollments', function ($q) use ($class, $schoolYear) {
            $q->where('class_id', $class->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('statut', 'validee');
        })->where('is_active', true)->get();

        $previews = [];

        foreach ($students as $student) {
            $gradesCount = GradeCollege::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('trimestre', $validated['trimestre'])
                ->where('school_year_id', $schoolYear->id)
                ->count();

            $existing = ReportCardCollege::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('trimestre', $validated['trimestre'])
                ->where('school_year_id', $schoolYear->id)
                ->first();

            $status = 'incomplete';
            if ($existing) {
                $status = 'generated';
            } elseif ($gradesCount >= 3) { // Standard: au moins 3 notes
                $status = 'ready';
            }

            $previews[] = [
                'student_id' => $student->id,
                'student_name' => $student->nom . ' ' . $student->prenoms,
                'matricule' => $student->matricule,
                'moyenne_generale' => $existing ? round($existing->moyenne_generale, 2) : 0,
                'rang' => $existing ? $existing->rang : 0,
                'grades_count' => $gradesCount,
                'status' => $status,
                'pdf_url' => $existing && $existing->pdf_path ? asset('storage/' . $existing->pdf_path) : null,
            ];
        }

        // Tri par moyenne
        usort($previews, fn($a, $b) => $b['moyenne_generale'] <=> $a['moyenne_generale']);

        $rank = 1;
        foreach ($previews as &$p) {
            if ($p['status'] !== 'incomplete') $p['rang'] = $rank++;
        }

        return response()->json([
            'data' => $previews,
            'class' => $class,
            'trimestre' => $validated['trimestre']
        ]);
    }

    /**
     * Génération groupée
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
            'student_ids' => 'required|array',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $generated = 0;
        $urls = [];

        foreach ($validated['student_ids'] as $studentId) {
            try {
                $bulletin = $this->reportCardService->generateForStudent(
                    $studentId,
                    $validated['class_id'],
                    $schoolYear->id,
                    (int)$validated['trimestre']
                );

                $urls[$studentId] = $bulletin->pdf_path
                    ? asset('storage/' . $bulletin->pdf_path)
                    : null;

                $generated++;
            } catch (\Exception $e) {
                continue;
            }
        }

        // Recalculer les rangs et statistiques de la classe
        $this->reportCardService->calculateClassStatistics(
            $validated['class_id'],
            (int)$validated['trimestre'],
            $schoolYear->id
        );

        AuditLog::log('report_cards_generated', ReportCardCollege::class, null, null, [
            'class_id' => $validated['class_id'],
            'trimestre' => $validated['trimestre'],
            'count' => $generated
        ]);

        return response()->json([
            'message' => "{$generated} bulletins générés avec succès.",
            'generated' => $generated,
            'urls' => $urls
        ]);
    }

    /**
     * Téléchargement ZIP (Placeholder)
     */
    public function downloadAll(Request $request)
    {
        return response()->json(['message' => 'Bientôt disponible.'], 501);
    }
}
