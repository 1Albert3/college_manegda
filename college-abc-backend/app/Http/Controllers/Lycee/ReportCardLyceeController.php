<?php

namespace App\Http\Controllers\Lycee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Lycee\ReportCardLycee;
use App\Models\Lycee\GradeLycee;
use App\Models\Lycee\StudentLycee;
use App\Models\Lycee\ClassLycee;
use App\Models\SchoolYear;
use App\Services\Lycee\ReportCardLyceeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Contrôleur des Bulletins Lycée
 */
class ReportCardLyceeController extends Controller
{
    protected ReportCardLyceeService $reportCardService;

    public function __construct(ReportCardLyceeService $reportCardService)
    {
        $this->reportCardService = $reportCardService;
    }

    /**
     * Liste des bulletins
     */
    public function index(Request $request)
    {
        $query = ReportCardLycee::with(['student', 'class', 'schoolYear']);

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
            'class_id' => 'required|uuid|exists:school_lycee.classes_lycee,id',
            'trimestre' => 'required|in:1,2,3',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $class = ClassLycee::findOrFail($validated['class_id']);

        // Récupérer les élèves via les inscriptions (Enrollments)
        $students = StudentLycee::whereHas('enrollments', function ($q) use ($class, $schoolYear) {
            $q->where('class_id', $class->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('statut', 'validee');
        })->where('is_active', true)->get();

        $previews = [];

        foreach ($students as $student) {
            $gradesCount = GradeLycee::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('trimestre', $validated['trimestre'])
                ->where('school_year_id', $schoolYear->id)
                ->count();

            $existing = ReportCardLycee::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('trimestre', $validated['trimestre'])
                ->where('school_year_id', $schoolYear->id)
                ->first();

            $status = 'incomplete';
            if ($existing) {
                $status = 'generated';
            } elseif ($gradesCount >= 2) { // Au Lycée, 2 notes par matière suffisent parfois? Disons 3 par défaut.
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

        // Tri par moyenne (provisoire ou réelle)
        usort($previews, fn($a, $b) => $b['moyenne_generale'] <=> $a['moyenne_generale']);

        // Attribution rangs provisoires
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

        AuditLog::log('report_cards_generated', ReportCardLycee::class, null, null, [
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
     * Génération de tous les bulletins d'une classe
     * Avec calcul automatique des statistiques et des rangs
     */
    public function generateForClass(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid|exists:school_lycee.classes_lycee,id',
            'trimestre' => 'required|in:1,2,3',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        try {
            $result = $this->reportCardService->generateForClass(
                $validated['class_id'],
                $schoolYear->id,
                (int)$validated['trimestre']
            );

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'count' => 0
                ], 422);
            }

            // Log de l'action
            AuditLog::log('report_cards_class_generated', ReportCardLycee::class, null, null, [
                'class_id' => $validated['class_id'],
                'trimestre' => $validated['trimestre'],
                'count' => $result['count'],
                'stats' => $result['stats']
            ]);

            return response()->json([
                'message' => $result['message'],
                'count' => $result['count'],
                'stats' => $result['stats'],
                'pdf_paths' => array_map(fn($path) => asset('storage/' . $path), $result['pdf_paths'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération: ' . $e->getMessage(),
                'count' => 0
            ], 500);
        }
    }

    /**
     * Téléchargement ZIP
     */
    public function downloadAll(Request $request)
    {
        // ... Logique ZIP similaire à MP ...
        return response()->json(['message' => 'Fonctionnalité en cours de déploiement.'], 501);
    }
}
