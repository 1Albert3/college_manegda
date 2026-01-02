<?php

namespace App\Http\Controllers\Examen;

use App\Http\Controllers\Controller;
use App\Models\MP\StudentMP;
use App\Models\MP\EnrollmentMP;
use App\Models\MP\ReportCardMP;
use App\Models\SchoolYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

/**
 * Contrôleur CEP - Certificat d'Études Primaires
 * 
 * Gestion des candidatures et dossiers pour l'examen national CEP
 * Niveau: CM2
 */
class CEPController extends Controller
{
    /**
     * Liste des candidats potentiels (élèves de CM2)
     */
    public function candidates(Request $request)
    {
        $schoolYear = SchoolYear::current();

        $query = EnrollmentMP::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', 'CM2'))
            ->with([
                'student:id,matricule,nom,prenoms,date_naissance,lieu_naissance,sexe,nationalite,photo_url',
                'student.guardian:id,student_id,nom,prenoms,telephone,email',
                'class:id,niveau,nom'
            ]);

        // Filtres
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas(
                'student',
                fn($q) => $q
                    ->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenoms', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%")
            );
        }

        $candidates = $query->get()->map(function ($enrollment) use ($schoolYear) {
            $student = $enrollment->student;

            // Calculer la moyenne annuelle
            $reportCards = ReportCardMP::where('student_id', $student->id)
                ->where('class_id', $enrollment->class_id)
                ->where('school_year_id', $schoolYear->id)
                ->get();

            $moyenneAnnuelle = $reportCards->avg('moyenne_generale') ?? 0;

            // Vérifier l'éligibilité
            $eligible = $this->checkEligibility($student, $moyenneAnnuelle);

            return [
                'enrollment_id' => $enrollment->id,
                'student' => [
                    'id' => $student->id,
                    'matricule' => $student->matricule,
                    'nom' => $student->nom,
                    'prenoms' => $student->prenoms,
                    'full_name' => $student->full_name,
                    'date_naissance' => $student->date_naissance?->format('d/m/Y'),
                    'lieu_naissance' => $student->lieu_naissance,
                    'sexe' => $student->sexe,
                    'nationalite' => $student->nationalite,
                    'photo_url' => $student->photo_url,
                    'age' => $student->date_naissance?->age,
                ],
                'class' => $enrollment->class?->full_name,
                'guardian' => $student->guardian?->only(['nom', 'prenoms', 'telephone', 'email']),
                'moyenne_annuelle' => round($moyenneAnnuelle, 2),
                'eligible' => $eligible['status'],
                'eligibility_notes' => $eligible['notes'],
                'dossier_status' => $this->getDossierStatus($student->id),
            ];
        });

        // Statistiques
        $stats = [
            'total' => $candidates->count(),
            'eligible' => $candidates->where('eligible', true)->count(),
            'non_eligible' => $candidates->where('eligible', false)->count(),
            'dossier_complet' => $candidates->where('dossier_status', 'complet')->count(),
            'moyenne_generale' => round($candidates->avg('moyenne_annuelle'), 2),
        ];

        return response()->json([
            'candidates' => $candidates,
            'stats' => $stats,
        ]);
    }

    /**
     * Vérifier l'éligibilité d'un candidat
     */
    private function checkEligibility($student, float $moyenneAnnuelle): array
    {
        $notes = [];
        $eligible = true;

        // Âge maximum (16 ans au 31 décembre)
        if ($student->date_naissance) {
            $ageAtEndOfYear = $student->date_naissance->diffInYears(now()->endOfYear());
            if ($ageAtEndOfYear > 16) {
                $eligible = false;
                $notes[] = "Âge supérieur à 16 ans ({$ageAtEndOfYear} ans)";
            }
        }

        // Moyenne minimum
        if ($moyenneAnnuelle < 5) {
            $eligible = false;
            $notes[] = "Moyenne inférieure à 5/20";
        }

        // Extrait de naissance
        if (empty($student->extrait_naissance_path)) {
            $notes[] = "Extrait de naissance manquant";
        }

        // Photo
        if (empty($student->photo_url)) {
            $notes[] = "Photo d'identité manquante";
        }

        return [
            'status' => $eligible,
            'notes' => $notes,
        ];
    }

    /**
     * Statut du dossier
     */
    private function getDossierStatus(string $studentId): string
    {
        $student = StudentMP::find($studentId);

        $required = [
            'extrait_naissance_path',
            'photo_url',
        ];

        $complete = true;
        foreach ($required as $field) {
            if (empty($student->$field)) {
                $complete = false;
                break;
            }
        }

        return $complete ? 'complet' : 'incomplet';
    }

    /**
     * Détails d'un candidat
     */
    public function show(string $studentId)
    {
        $student = StudentMP::with([
            'guardian',
            'enrollments' => fn($q) => $q->with('class')->orderByDesc('school_year_id')
        ])->findOrFail($studentId);

        $schoolYear = SchoolYear::current();
        $currentEnrollment = $student->enrollments->first();

        // Bulletins
        $reportCards = ReportCardMP::where('student_id', $studentId)
            ->where('school_year_id', $schoolYear->id)
            ->orderBy('trimestre')
            ->get();

        // Historique scolaire
        $history = $student->enrollments->take(5)->map(fn($e) => [
            'year' => $e->schoolYear?->name,
            'class' => $e->class?->full_name,
            'decision' => $e->decision_finale,
        ]);

        return response()->json([
            'student' => $student,
            'current_class' => $currentEnrollment?->class?->full_name,
            'report_cards' => $reportCards,
            'history' => $history,
            'dossier' => $this->getDossierDetails($student),
        ]);
    }

    /**
     * Détails du dossier
     */
    private function getDossierDetails($student): array
    {
        return [
            'pieces' => [
                ['name' => 'Extrait de naissance', 'status' => !empty($student->extrait_naissance_path), 'file' => $student->extrait_naissance_path],
                ['name' => 'Photo d\'identité', 'status' => !empty($student->photo_url), 'file' => $student->photo_url],
                ['name' => 'Certificat de nationalité', 'status' => !empty($student->certificat_nationalite_path), 'file' => $student->certificat_nationalite_path],
            ],
            'complete' => !empty($student->extrait_naissance_path) && !empty($student->photo_url),
        ];
    }

    /**
     * Exporter les données au format officiel
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:excel,csv,pdf',
            'class_ids' => 'nullable|array',
        ]);

        $schoolYear = SchoolYear::current();

        $query = EnrollmentMP::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', 'CM2'))
            ->with(['student.guardian', 'class']);

        if (!empty($validated['class_ids'])) {
            $query->whereIn('class_id', $validated['class_ids']);
        }

        $enrollments = $query->get();

        $data = $enrollments->map(function ($e, $index) {
            $s = $e->student;
            return [
                'N°' => $index + 1,
                'Matricule' => $s->matricule,
                'Nom' => strtoupper($s->nom),
                'Prénom(s)' => $s->prenoms,
                'Date de naissance' => $s->date_naissance?->format('d/m/Y'),
                'Lieu de naissance' => $s->lieu_naissance,
                'Sexe' => $s->sexe === 'M' ? 'Masculin' : 'Féminin',
                'Nationalité' => $s->nationalite,
                'Nom du père' => $s->guardian?->nom_pere ?? $s->guardian?->nom,
                'Nom de la mère' => $s->guardian?->nom_mere,
                'Classe' => $e->class?->full_name,
                'Téléphone parent' => $s->guardian?->telephone,
            ];
        });

        AuditLog::log('cep_candidates_exported', null, null, null, [
            'format' => $validated['format'],
            'count' => $data->count(),
        ]);

        // Retourner selon le format
        if ($validated['format'] === 'csv') {
            return $this->exportCSV($data, 'candidats_cep');
        }

        if ($validated['format'] === 'pdf') {
            return $this->exportPDF($data, 'candidats_cep');
        }

        // Excel par défaut (via JSON pour le frontend)
        return response()->json([
            'data' => $data,
            'filename' => 'candidats_cep_' . date('Y'),
        ]);
    }

    /**
     * Export CSV
     */
    private function exportCSV($data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // En-têtes
            fputcsv($file, array_keys($data->first() ?? []));

            // Données
            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export PDF
     */
    private function exportPDF($data, string $filename)
    {
        $pdf = PDF::loadView('pdf.cep_candidates', [
            'candidates' => $data,
            'school_year' => SchoolYear::current()->name,
            'school_name' => config('app.school_name'),
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }

    /**
     * Générer les fiches individuelles
     */
    public function generateFiches(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
        ]);

        $students = StudentMP::whereIn('id', $validated['student_ids'])
            ->with('guardian')
            ->get();

        $pdf = PDF::loadView('pdf.cep_fiches', [
            'students' => $students,
            'school_year' => SchoolYear::current()->name,
            'school_name' => config('app.school_name'),
        ]);

        return $pdf->download('fiches_cep_' . date('Ymd') . '.pdf');
    }

    /**
     * Statistiques générales
     */
    public function stats()
    {
        $schoolYear = SchoolYear::current();

        // Total CM2
        $totalCM2 = EnrollmentMP::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', 'CM2'))
            ->count();

        // Par sexe
        $bySex = DB::connection('school_mp')
            ->table('enrollments_mp as e')
            ->join('students_mp as s', 'e.student_id', '=', 's.id')
            ->join('classes_mp as c', 'e.class_id', '=', 'c.id')
            ->where('e.school_year_id', $schoolYear->id)
            ->where('e.statut', 'validee')
            ->where('c.niveau', 'CM2')
            ->select('s.sexe', DB::raw('COUNT(*) as count'))
            ->groupBy('s.sexe')
            ->get()
            ->keyBy('sexe');

        // Par classe
        $byClass = DB::connection('school_mp')
            ->table('enrollments_mp as e')
            ->join('classes_mp as c', 'e.class_id', '=', 'c.id')
            ->where('e.school_year_id', $schoolYear->id)
            ->where('e.statut', 'validee')
            ->where('c.niveau', 'CM2')
            ->select('c.id', 'c.nom', DB::raw('COUNT(*) as count'))
            ->groupBy('c.id', 'c.nom')
            ->get();

        return response()->json([
            'total' => $totalCM2,
            'by_sex' => [
                'masculin' => $bySex->get('M')?->count ?? 0,
                'feminin' => $bySex->get('F')?->count ?? 0,
            ],
            'by_class' => $byClass,
        ]);
    }
}
