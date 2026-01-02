<?php

namespace App\Http\Controllers\Examen;

use App\Http\Controllers\Controller;
use App\Models\Lycee\StudentLycee;
use App\Models\Lycee\EnrollmentLycee;
use App\Models\Lycee\ReportCardLycee;
use App\Models\SchoolYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

/**
 * Contrôleur BAC - Baccalauréat
 * 
 * Gestion des candidatures et dossiers pour l'examen national du Baccalauréat
 * Niveau: Terminale (Tle)
 */
class BACController extends Controller
{
    /**
     * Séries du Baccalauréat burkinabè
     */
    const SERIES = [
        'A' => 'Lettres et Philosophie',
        'C' => 'Mathématiques et Sciences Physiques',
        'D' => 'Sciences de la Vie et de la Terre',
        'E' => 'Mathématiques et Technique',
        'F' => 'Sciences et Technologies Industrielles',
        'G' => 'Sciences et Technologies de Gestion',
    ];

    /**
     * Liste des candidats potentiels (élèves de Terminale)
     */
    public function candidates(Request $request)
    {
        $schoolYear = SchoolYear::current();

        $query = EnrollmentLycee::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', 'Tle'))
            ->with([
                'student:id,matricule,nom,prenoms,date_naissance,lieu_naissance,sexe,nationalite,photo_url',
                'student.guardian:id,student_id,nom,prenoms,telephone,email',
                'class:id,niveau,nom,serie'
            ]);

        // Filtres
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('serie')) {
            $query->whereHas('class', fn($q) => $q->where('serie', $request->serie));
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

            // Moyennes Première + Terminale
            $moyennePremiere = $this->getMoyennePremiere($student->id);
            $moyenneTerminale = $this->getMoyenneTerminale($student->id, $schoolYear->id);

            // Moyenne annuelle (pondérée)
            $moyenneAnnuelle = ($moyennePremiere * 0.4) + ($moyenneTerminale * 0.6);

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
                ],
                'class' => $enrollment->class?->full_name,
                'serie' => $enrollment->class?->serie,
                'serie_name' => self::SERIES[$enrollment->class?->serie] ?? $enrollment->class?->serie,
                'guardian' => $student->guardian?->only(['nom', 'prenoms', 'telephone', 'email']),
                'moyenne_premiere' => round($moyennePremiere, 2),
                'moyenne_terminale' => round($moyenneTerminale, 2),
                'moyenne_generale' => round($moyenneAnnuelle, 2),
                'eligible' => $eligible['status'],
                'eligibility_notes' => $eligible['notes'],
                'dossier_status' => $this->getDossierStatus($student->id),
            ];
        });

        // Statistiques
        $stats = [
            'total' => $candidates->count(),
            'eligible' => $candidates->where('eligible', true)->count(),
            'by_serie' => $candidates->groupBy('serie')->map->count(),
            'by_sex' => [
                'M' => $candidates->where('student.sexe', 'M')->count(),
                'F' => $candidates->where('student.sexe', 'F')->count(),
            ],
            'moyenne_generale' => round($candidates->avg('moyenne_generale'), 2),
        ];

        return response()->json([
            'candidates' => $candidates,
            'stats' => $stats,
            'series' => self::SERIES,
        ]);
    }

    /**
     * Moyenne de Première
     */
    private function getMoyennePremiere(string $studentId): float
    {
        // Chercher l'inscription en Première (année précédente)
        $previousYear = SchoolYear::where('is_current', false)
            ->orderByDesc('date_debut')
            ->first();

        if (!$previousYear) return 0;

        $reportCards = ReportCardLycee::where('student_id', $studentId)
            ->where('school_year_id', $previousYear->id)
            ->get();

        return $reportCards->avg('moyenne_generale') ?? 0;
    }

    /**
     * Moyenne de Terminale
     */
    private function getMoyenneTerminale(string $studentId, string $schoolYearId): float
    {
        $reportCards = ReportCardLycee::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->get();

        return $reportCards->avg('moyenne_generale') ?? 0;
    }

    /**
     * Vérifier l'éligibilité
     */
    private function checkEligibility($student, float $moyenneAnnuelle): array
    {
        $notes = [];
        $eligible = true;

        // Âge maximum (25 ans)
        if ($student->date_naissance) {
            $ageAtEndOfYear = $student->date_naissance->diffInYears(now()->endOfYear());
            if ($ageAtEndOfYear > 25) {
                $eligible = false;
                $notes[] = "Âge supérieur à 25 ans";
            }
        }

        // Moyenne minimum
        if ($moyenneAnnuelle < 4) {
            $eligible = false;
            $notes[] = "Moyenne inférieure à 4/20";
        }

        // Documents
        if (empty($student->extrait_naissance_path)) {
            $notes[] = "Extrait de naissance manquant";
        }
        if (empty($student->photo_url)) {
            $notes[] = "Photo d'identité manquante";
        }
        if (empty($student->cnib_path)) {
            $notes[] = "CNIB manquante";
        }
        if (empty($student->releve_premiere_path)) {
            $notes[] = "Relevé de notes de Première manquant";
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
        $student = StudentLycee::find($studentId);

        $required = ['extrait_naissance_path', 'photo_url', 'cnib_path'];

        foreach ($required as $field) {
            if (empty($student->$field)) {
                return 'incomplet';
            }
        }

        return 'complet';
    }

    /**
     * Détails d'un candidat
     */
    public function show(string $studentId)
    {
        $student = StudentLycee::with([
            'guardian',
            'enrollments' => fn($q) => $q->with('class')->orderByDesc('school_year_id')
        ])->findOrFail($studentId);

        $schoolYear = SchoolYear::current();

        // Bulletins Terminale
        $reportCardsTerminale = ReportCardLycee::where('student_id', $studentId)
            ->where('school_year_id', $schoolYear->id)
            ->orderBy('trimestre')
            ->get();

        // Notes par matière
        $gradesBySubject = DB::connection('school_lycee')
            ->table('grades_lycee as g')
            ->join('subjects_lycee as s', 'g.subject_id', '=', 's.id')
            ->where('g.student_id', $studentId)
            ->where('g.school_year_id', $schoolYear->id)
            ->select('s.nom', 's.coefficient', DB::raw('AVG(g.note_sur_20) as moyenne'))
            ->groupBy('s.id', 's.nom', 's.coefficient')
            ->get();

        // Parcours lycée
        $parcours = $student->enrollments->map(fn($e) => [
            'year' => $e->schoolYear?->name,
            'class' => $e->class?->full_name,
            'serie' => $e->class?->serie,
        ]);

        return response()->json([
            'student' => $student,
            'serie' => $student->enrollments->first()?->class?->serie,
            'report_cards' => $reportCardsTerminale,
            'grades_by_subject' => $gradesBySubject,
            'parcours' => $parcours,
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
                ['name' => 'Extrait de naissance (original)', 'status' => !empty($student->extrait_naissance_path), 'required' => true],
                ['name' => 'Photos d\'identité (4)', 'status' => !empty($student->photo_url), 'required' => true],
                ['name' => 'CNIB ou Passeport', 'status' => !empty($student->cnib_path), 'required' => true],
                ['name' => 'Relevé de notes de Première', 'status' => !empty($student->releve_premiere_path), 'required' => true],
                ['name' => 'Attestation BEPC', 'status' => !empty($student->attestation_bepc_path), 'required' => true],
                ['name' => 'Certificat de nationalité', 'status' => !empty($student->certificat_nationalite_path), 'required' => false],
            ],
        ];
    }

    /**
     * Exporter au format officiel
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:excel,csv,pdf,office',
            'serie' => 'nullable|string',
            'class_ids' => 'nullable|array',
        ]);

        $schoolYear = SchoolYear::current();

        $query = EnrollmentLycee::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', 'Tle'))
            ->with(['student.guardian', 'class']);

        if (!empty($validated['serie'])) {
            $query->whereHas('class', fn($q) => $q->where('serie', $validated['serie']));
        }

        if (!empty($validated['class_ids'])) {
            $query->whereIn('class_id', $validated['class_ids']);
        }

        $enrollments = $query->get();

        // Format Office du Bac
        if ($validated['format'] === 'office') {
            return $this->exportOfficeBac($enrollments);
        }

        $data = $enrollments->map(function ($e, $index) {
            $s = $e->student;
            return [
                'N°' => $index + 1,
                'Matricule' => $s->matricule,
                'Nom' => strtoupper($s->nom),
                'Prénom(s)' => $s->prenoms,
                'Date naissance' => $s->date_naissance?->format('d/m/Y'),
                'Lieu naissance' => $s->lieu_naissance,
                'Sexe' => $s->sexe,
                'Nationalité' => $s->nationalite ?? 'Burkinabè',
                'Série' => $e->class?->serie,
                'Centre examen' => config('app.exam_center', 'OUAGADOUGOU'),
                'Établissement' => config('app.school_name'),
            ];
        });

        AuditLog::log('bac_candidates_exported', null, null, null, [
            'format' => $validated['format'],
            'count' => $data->count(),
        ]);

        if ($validated['format'] === 'pdf') {
            $pdf = PDF::loadView('pdf.bac_candidates', [
                'candidates' => $data,
                'school_year' => $schoolYear->name,
                'school_name' => config('app.school_name'),
            ]);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('candidats_bac.pdf');
        }

        return response()->json([
            'data' => $data,
            'filename' => 'candidats_bac_' . date('Y'),
        ]);
    }

    /**
     * Export format Office du Bac
     */
    private function exportOfficeBac($enrollments)
    {
        $data = $enrollments->map(function ($e, $index) {
            $s = $e->student;
            return [
                'NUM' => str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'MAT' => $s->matricule,
                'NOM' => strtoupper($s->nom),
                'PRENOM' => strtoupper($s->prenoms),
                'DNAISS' => $s->date_naissance?->format('dmY'),
                'LNAISS' => strtoupper($s->lieu_naissance ?? ''),
                'SEXE' => $s->sexe,
                'NAT' => 'BF',
                'SERIE' => $e->class?->serie,
                'CENTRE' => config('app.exam_center_code', '000'),
                'ETAB' => config('app.school_code', '000000'),
            ];
        });

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=CANDIDATS_BAC_OFFICE.csv',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, array_keys($data->first() ?? []), ';');

            foreach ($data as $row) {
                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Statistiques BAC
     */
    public function stats()
    {
        $schoolYear = SchoolYear::current();

        $total = EnrollmentLycee::where('school_year_id', $schoolYear->id)
            ->where('statut', 'validee')
            ->whereHas('class', fn($q) => $q->where('niveau', 'Tle'))
            ->count();

        // Par série
        $bySerie = DB::connection('school_lycee')
            ->table('enrollments_lycee as e')
            ->join('classes_lycee as c', 'e.class_id', '=', 'c.id')
            ->where('e.school_year_id', $schoolYear->id)
            ->where('e.statut', 'validee')
            ->where('c.niveau', 'Tle')
            ->select('c.serie', DB::raw('COUNT(*) as count'))
            ->groupBy('c.serie')
            ->get()
            ->keyBy('serie');

        // Par sexe
        $bySex = DB::connection('school_lycee')
            ->table('enrollments_lycee as e')
            ->join('students_lycee as s', 'e.student_id', '=', 's.id')
            ->join('classes_lycee as c', 'e.class_id', '=', 'c.id')
            ->where('e.school_year_id', $schoolYear->id)
            ->where('e.statut', 'validee')
            ->where('c.niveau', 'Tle')
            ->select('s.sexe', DB::raw('COUNT(*) as count'))
            ->groupBy('s.sexe')
            ->get()
            ->keyBy('sexe');

        return response()->json([
            'total' => $total,
            'by_serie' => $bySerie,
            'by_sex' => [
                'masculin' => $bySex->get('M')?->count ?? 0,
                'feminin' => $bySex->get('F')?->count ?? 0,
            ],
            'series_available' => self::SERIES,
        ]);
    }
}
