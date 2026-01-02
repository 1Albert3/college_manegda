<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MP\ReportCardMP;
use App\Models\MP\GradeMP;
use App\Models\MP\StudentMP;
use App\Models\MP\ClassMP;
use App\Models\SchoolYear;
use App\Services\ReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Contrôleur des Bulletins Maternelle/Primaire
 * 
 * Fonctionnalités:
 * - Prévisualisation avant génération
 * - Génération individuelle et par lot
 * - Téléchargement ZIP de tous les bulletins
 */
class ReportCardMPController extends Controller
{
    protected ReportCardService $reportCardService;

    public function __construct(ReportCardService $reportCardService)
    {
        $this->reportCardService = $reportCardService;
    }

    /**
     * Liste des bulletins avec filtres
     */
    public function index(Request $request)
    {
        $query = ReportCardMP::with(['student', 'class', 'schoolYear']);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('trimestre')) {
            $query->where('trimestre', $request->trimestre);
        }

        $schoolYearId = $request->get('school_year_id', SchoolYear::current()?->id);
        if ($schoolYearId) {
            $query->where('school_year_id', $schoolYearId);
        }

        $reportCards = $query->orderByDesc('created_at')->paginate($request->per_page ?? 20);

        return response()->json($reportCards);
    }

    /**
     * Afficher un bulletin
     */
    public function show(string $id)
    {
        $reportCard = ReportCardMP::with([
            'student',
            'class',
            'schoolYear',
            'grades.subject'
        ])->findOrFail($id);

        return response()->json($reportCard);
    }

    /**
     * Prévisualisation des bulletins d'une classe (avant génération)
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $class = ClassMP::with(['enrollments' => function ($q) use ($schoolYear) {
            $q->where('school_year_id', $schoolYear->id)
                ->where('statut', 'validee');
        }, 'enrollments.student'])->findOrFail($validated['class_id']);

        $previews = [];

        foreach ($class->enrollments as $enrollment) {
            $student = $enrollment->student;

            // Compter les notes disponibles
            $gradesCount = GradeMP::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('trimestre', $validated['trimestre'])
                ->where('school_year_id', $schoolYear->id)
                ->count();

            // Vérifier si bulletin existe déjà
            $existingReportCard = ReportCardMP::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('trimestre', $validated['trimestre'])
                ->where('school_year_id', $schoolYear->id)
                ->first();

            // Calculer la moyenne si assez de notes
            $moyenne = 0;
            $rang = 0;
            $mention = '-';
            $status = 'incomplete';

            if ($gradesCount >= 3) { // Minimum 3 notes pour générer
                $grades = GradeMP::where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('trimestre', $validated['trimestre'])
                    ->where('school_year_id', $schoolYear->id)
                    ->get();

                $moyenne = $grades->avg('note_sur_20') ?? 0;
                $mention = $this->getMention($moyenne);
                $status = 'ready';
            }

            if ($existingReportCard) {
                $moyenne = $existingReportCard->moyenne_generale;
                $rang = $existingReportCard->rang;
                $mention = $existingReportCard->mention;
                $status = 'generated';
            }

            $previews[] = [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'matricule' => $student->matricule,
                'moyenne_generale' => round($moyenne, 2),
                'rang' => $rang,
                'mention' => $mention,
                'grades_count' => $gradesCount,
                'status' => $status,
                'pdf_url' => $existingReportCard?->pdf_path
                    ? asset('storage/' . $existingReportCard->pdf_path)
                    : null,
            ];
        }

        // Calculer les rangs provisoires pour les "ready"
        $readyPreviews = collect($previews)
            ->filter(fn($p) => $p['status'] === 'ready')
            ->sortByDesc('moyenne_generale')
            ->values();

        $currentRank = 1;
        foreach ($readyPreviews as $index => $preview) {
            $previewIndex = array_search($preview['student_id'], array_column($previews, 'student_id'));
            if ($previewIndex !== false) {
                $previews[$previewIndex]['rang'] = $currentRank++;
            }
        }

        // Trier par rang
        usort($previews, function ($a, $b) {
            if ($a['status'] === 'incomplete') return 1;
            if ($b['status'] === 'incomplete') return -1;
            return $a['rang'] <=> $b['rang'];
        });

        return response()->json([
            'data' => $previews,
            'class' => $class->only(['id', 'niveau', 'nom']),
            'trimestre' => $validated['trimestre'],
            'school_year' => $schoolYear->name,
        ]);
    }

    /**
     * Générer les bulletins
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'uuid',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $generated = 0;
        $urls = [];

        foreach ($validated['student_ids'] as $studentId) {
            try {
                $reportCard = $this->reportCardService->generateBulletin(
                    $studentId,
                    $validated['class_id'],
                    $validated['trimestre'],
                    $schoolYear->id
                );

                $urls[$studentId] = $reportCard->pdf_path
                    ? asset('storage/' . $reportCard->pdf_path)
                    : null;

                $generated++;
            } catch (\Exception $e) {
                // Continuer avec les autres élèves
                continue;
            }
        }

        AuditLog::log('report_cards_generated', ReportCardMP::class, null, null, [
            'class_id' => $validated['class_id'],
            'trimestre' => $validated['trimestre'],
            'count' => $generated,
        ]);

        return response()->json([
            'message' => "{$generated} bulletin(s) généré(s) avec succès.",
            'generated' => $generated,
            'urls' => $urls,
        ]);
    }

    /**
     * Prévisualiser un bulletin en PDF (sans sauvegarder)
     */
    public function previewPdf(Request $request, string $studentId)
    {
        $validated = $request->validate([
            'trimestre' => 'required|in:1,2,3',
        ]);

        $schoolYear = SchoolYear::current();
        $student = StudentMP::findOrFail($studentId);

        // Récupérer l'inscription et la classe
        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json(['message' => 'Élève non inscrit.'], 422);
        }

        $pdf = $this->reportCardService->generatePdfPreview(
            $student,
            $enrollment->class,
            $validated['trimestre'],
            $schoolYear
        );

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="bulletin_preview.pdf"');
    }

    /**
     * Télécharger tous les bulletins d'une classe en ZIP
     */
    public function downloadAll(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $class = ClassMP::findOrFail($validated['class_id']);

        // Récupérer tous les bulletins générés
        $reportCards = ReportCardMP::where('class_id', $validated['class_id'])
            ->where('trimestre', $validated['trimestre'])
            ->where('school_year_id', $schoolYear->id)
            ->whereNotNull('pdf_path')
            ->with('student')
            ->get();

        if ($reportCards->isEmpty()) {
            return response()->json(['message' => 'Aucun bulletin généré pour cette classe.'], 422);
        }

        // Créer le fichier ZIP
        $zipFileName = "bulletins_{$class->niveau}_{$class->nom}_T{$validated['trimestre']}.zip";
        $zipPath = "temp/{$zipFileName}";

        $zip = new ZipArchive();
        $fullZipPath = Storage::disk('local')->path($zipPath);

        // S'assurer que le dossier temp existe
        Storage::disk('local')->makeDirectory('temp');

        if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Erreur lors de la création du fichier ZIP.'], 500);
        }

        foreach ($reportCards as $reportCard) {
            $pdfFullPath = Storage::disk('public')->path($reportCard->pdf_path);
            if (file_exists($pdfFullPath)) {
                $fileName = "Bulletin_{$reportCard->student->nom}_{$reportCard->student->prenoms}_T{$validated['trimestre']}.pdf";
                $zip->addFile($pdfFullPath, $fileName);
            }
        }

        $zip->close();

        // Télécharger et supprimer
        return response()->download($fullZipPath, $zipFileName)
            ->deleteFileAfterSend(true);
    }

    /**
     * Supprimer un bulletin (pour régénération)
     */
    public function destroy(string $id)
    {
        $reportCard = ReportCardMP::findOrFail($id);

        // Supprimer le fichier PDF
        if ($reportCard->pdf_path && Storage::disk('public')->exists($reportCard->pdf_path)) {
            Storage::disk('public')->delete($reportCard->pdf_path);
        }

        $reportCard->delete();

        AuditLog::log('report_card_deleted', ReportCardMP::class, $id);

        return response()->json([
            'message' => 'Bulletin supprimé avec succès.',
        ]);
    }

    /**
     * Publier les bulletins (visible aux parents)
     */
    public function publish(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
        ]);

        $schoolYear = SchoolYear::current();

        $count = ReportCardMP::where('class_id', $validated['class_id'])
            ->where('trimestre', $validated['trimestre'])
            ->where('school_year_id', $schoolYear->id)
            ->where('is_published', false)
            ->update([
                'is_published' => true,
                'published_at' => now(),
            ]);

        AuditLog::log('report_cards_published', ReportCardMP::class, null, null, [
            'class_id' => $validated['class_id'],
            'trimestre' => $validated['trimestre'],
            'count' => $count,
        ]);

        return response()->json([
            'message' => "{$count} bulletin(s) publié(s).",
            'published' => $count,
        ]);
    }

    /**
     * Obtenir la mention selon la moyenne
     */
    private function getMention(float $moyenne): string
    {
        if ($moyenne >= 18) return 'Excellent';
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Insuffisant';
    }
}
