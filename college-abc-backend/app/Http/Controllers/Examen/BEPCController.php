<?php

namespace App\Http\Controllers\Examen;

use App\Http\Controllers\Controller;
use App\Models\College\StudentCollege;
use App\Models\College\EnrollmentCollege;
use App\Models\College\ReportCardCollege;
use App\Models\SchoolYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

/**
 * Contrôleur BEPC - Brevet d'Études du Premier Cycle
 * 
 * Gestion des candidatures et dossiers pour l'examen national BEPC
 * Niveau: 3ème
 */
class BEPCController extends Controller
{
    /**
     * Liste des candidats potentiels (élèves de 3ème)
     */
    public function candidates(Request $request)
    {
        $schoolYear = SchoolYear::current();

        $query = EnrollmentCollege::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', '3eme'))
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
            $reportCards = ReportCardCollege::where('student_id', $student->id)
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

        // Âge maximum (21 ans au 31 décembre)
        if ($student->date_naissance) {
            $ageAtEndOfYear = $student->date_naissance->diffInYears(now()->endOfYear());
            if ($ageAtEndOfYear > 21) {
                $eligible = false;
                $notes[] = "Âge supérieur à 21 ans ({$ageAtEndOfYear} ans)";
            }
        }

        // Moyenne minimum (4/20 selon réglementation)
        if ($moyenneAnnuelle < 4) {
            $eligible = false;
            $notes[] = "Moyenne inférieure à 4/20";
        }

        // Documents requis
        if (empty($student->extrait_naissance_path)) {
            $notes[] = "Extrait de naissance manquant";
        }

        if (empty($student->photo_url)) {
            $notes[] = "Photo d'identité manquante";
        }

        if (empty($student->cnib_path)) {
            $notes[] = "CNIB/Carte d'identité manquante";
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
        $student = StudentCollege::find($studentId);

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
        $student = StudentCollege::with([
            'guardian',
            'enrollments' => fn($q) => $q->with('class')->orderByDesc('school_year_id')
        ])->findOrFail($studentId);

        $schoolYear = SchoolYear::current();
        $currentEnrollment = $student->enrollments->first();

        // Bulletins 3ème
        $reportCards = ReportCardCollege::where('student_id', $studentId)
            ->where('school_year_id', $schoolYear->id)
            ->orderBy('trimestre')
            ->get();

        // Notes par matière pour le relevé
        $gradesBySubject = DB::connection('school_college')
            ->table('grades_college as g')
            ->join('subjects_college as s', 'g.subject_id', '=', 's.id')
            ->where('g.student_id', $studentId)
            ->where('g.school_year_id', $schoolYear->id)
            ->select('s.nom as subject', 's.coefficient', DB::raw('AVG(g.note_sur_20) as moyenne'))
            ->groupBy('s.id', 's.nom', 's.coefficient')
            ->get();

        return response()->json([
            'student' => $student,
            'current_class' => $currentEnrollment?->class?->full_name,
            'report_cards' => $reportCards,
            'grades_by_subject' => $gradesBySubject,
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
                ['name' => 'Extrait de naissance', 'status' => !empty($student->extrait_naissance_path), 'required' => true],
                ['name' => 'Photo d\'identité récente', 'status' => !empty($student->photo_url), 'required' => true],
                ['name' => 'CNIB ou carte scolaire', 'status' => !empty($student->cnib_path), 'required' => false],
                ['name' => 'Certificat de nationalité', 'status' => !empty($student->certificat_nationalite_path), 'required' => false],
                ['name' => 'Certificat de scolarité', 'status' => true, 'required' => true], // Généré automatiquement
            ],
            'complete' => !empty($student->extrait_naissance_path) && !empty($student->photo_url),
        ];
    }

    /**
     * Exporter les données au format officiel DGESS
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:excel,csv,pdf,dgess',
            'class_ids' => 'nullable|array',
        ]);

        $schoolYear = SchoolYear::current();

        $query = EnrollmentCollege::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', '3eme'))
            ->with(['student.guardian', 'class']);

        if (!empty($validated['class_ids'])) {
            $query->whereIn('class_id', $validated['class_ids']);
        }

        $enrollments = $query->get();

        // Format DGESS spécifique
        if ($validated['format'] === 'dgess') {
            return $this->exportDGESS($enrollments);
        }

        $data = $enrollments->map(function ($e, $index) {
            $s = $e->student;
            return [
                'N° Ordre' => $index + 1,
                'Matricule' => $s->matricule,
                'Nom' => strtoupper($s->nom),
                'Prénom(s)' => $s->prenoms,
                'Date de naissance' => $s->date_naissance?->format('d/m/Y'),
                'Lieu de naissance' => $s->lieu_naissance,
                'Sexe' => $s->sexe,
                'Nationalité' => $s->nationalite ?? 'Burkinabè',
                'Établissement' => config('app.school_name'),
                'Classe' => $e->class?->full_name,
                'Nom père' => $s->guardian?->nom_pere ?? '',
                'Nom mère' => $s->guardian?->nom_mere ?? '',
                'Contact' => $s->guardian?->telephone ?? '',
            ];
        });

        AuditLog::log('bepc_candidates_exported', null, null, null, [
            'format' => $validated['format'],
            'count' => $data->count(),
        ]);

        if ($validated['format'] === 'csv') {
            return $this->exportCSV($data, 'candidats_bepc');
        }

        if ($validated['format'] === 'pdf') {
            $pdf = PDF::loadView('pdf.bepc_candidates', [
                'candidates' => $data,
                'school_year' => $schoolYear->name,
                'school_name' => config('app.school_name'),
            ]);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('candidats_bepc.pdf');
        }

        return response()->json([
            'data' => $data,
            'filename' => 'candidats_bepc_' . date('Y'),
        ]);
    }

    /**
     * Export format DGESS (Direction Générale des Études et Statistiques Scolaires)
     */
    private function exportDGESS($enrollments)
    {
        $data = $enrollments->map(function ($e, $index) {
            $s = $e->student;
            // Format spécifique DGESS Burkina Faso
            return [
                'NUM_ORDRE' => str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'MATRICULE' => $s->matricule,
                'NOM' => strtoupper($this->removeAccents($s->nom)),
                'PRENOM' => strtoupper($this->removeAccents($s->prenoms)),
                'DATE_NAISS' => $s->date_naissance?->format('dmY'),
                'LIEU_NAISS' => strtoupper($this->removeAccents($s->lieu_naissance ?? '')),
                'SEXE' => $s->sexe,
                'NATIONALITE' => 'BF', // Code pays
                'CODE_ETAB' => config('app.school_code', '000000'),
                'CLASSE' => '3EME',
                'REDOUBLANT' => $s->is_redoublant ? 'O' : 'N',
            ];
        });

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=CANDIDATS_BEPC_DGESS.csv',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($file, array_keys($data->first() ?? []), ';');

            foreach ($data as $row) {
                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Retirer les accents (pour format DGESS)
     */
    private function removeAccents(string $string): string
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    }

    /**
     * Export CSV standard
     */
    private function exportCSV($data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($data->first() ?? []));
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Générer les relevés de notes
     */
    public function generateReleves(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
        ]);

        $schoolYear = SchoolYear::current();

        $students = StudentCollege::whereIn('id', $validated['student_ids'])
            ->with('guardian')
            ->get();

        // Récupérer les notes pour chaque élève
        $studentsData = $students->map(function ($student) use ($schoolYear) {
            $enrollment = EnrollmentCollege::where('student_id', $student->id)
                ->where('school_year_id', $schoolYear->id)
                ->with('class')
                ->first();

            $grades = DB::connection('school_college')
                ->table('grades_college as g')
                ->join('subjects_college as s', 'g.subject_id', '=', 's.id')
                ->where('g.student_id', $student->id)
                ->where('g.school_year_id', $schoolYear->id)
                ->select('s.nom', 's.coefficient', DB::raw('AVG(g.note_sur_20) as moyenne'))
                ->groupBy('s.id', 's.nom', 's.coefficient')
                ->orderBy('s.nom')
                ->get();

            return [
                'student' => $student,
                'class' => $enrollment?->class?->full_name,
                'grades' => $grades,
                'moyenne_generale' => $grades->avg('moyenne'),
            ];
        });

        $pdf = PDF::loadView('pdf.bepc_releves', [
            'students' => $studentsData,
            'school_year' => $schoolYear->name,
            'school_name' => config('app.school_name'),
        ]);

        return $pdf->download('releves_notes_bepc_' . date('Ymd') . '.pdf');
    }

    /**
     * Statistiques BEPC
     */
    public function stats()
    {
        $schoolYear = SchoolYear::current();

        $total = EnrollmentCollege::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', '3eme'))
            ->count();

        $bySex = DB::connection('school_college')
            ->table('enrollments_college as e')
            ->join('students_college as s', 'e.student_id', '=', 's.id')
            ->join('classes_college as c', 'e.class_id', '=', 'c.id')
            ->where('e.school_year_id', $schoolYear->id)
            ->where('e.statut', 'validee')
            ->where('c.niveau', '3eme')
            ->select('s.sexe', DB::raw('COUNT(*) as count'))
            ->groupBy('s.sexe')
            ->get()
            ->keyBy('sexe');

        $byClass = DB::connection('school_college')
            ->table('enrollments_college as e')
            ->join('classes_college as c', 'e.class_id', '=', 'c.id')
            ->where('e.school_year_id', $schoolYear->id)
            ->where('e.statut', 'validee')
            ->where('c.niveau', '3eme')
            ->select('c.id', 'c.nom', DB::raw('COUNT(*) as count'))
            ->groupBy('c.id', 'c.nom')
            ->get();

        return response()->json([
            'total' => $total,
            'by_sex' => [
                'masculin' => $bySex->get('M')?->count ?? 0,
                'feminin' => $bySex->get('F')?->count ?? 0,
            ],
            'by_class' => $byClass,
        ]);
    }
}
