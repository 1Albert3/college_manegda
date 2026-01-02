<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MP\StudentMP;
use App\Models\MP\GuardianMP;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Contrôleur des élèves Maternelle/Primaire
 * 
 * CRUD complet + fonctionnalités:
 * - Recherche avancée
 * - Export Excel/PDF
 * - Import en masse
 * - Historique complet
 */
class StudentMPController extends Controller
{
    /**
     * Liste des élèves avec filtres et pagination
     */
    public function index(Request $request)
    {
        $query = StudentMP::with(['guardians', 'enrollments.class']);

        // Filtres
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenoms', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($request->has('niveau')) {
            $query->whereHas('enrollments.class', function ($q) use ($request) {
                $q->where('niveau', $request->niveau);
            });
        }

        if ($request->has('classe_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('class_id', $request->classe_id);
            });
        }

        if ($request->has('statut')) {
            $query->where('statut_inscription', $request->statut);
        }

        if ($request->has('sexe')) {
            $query->where('sexe', $request->sexe);
        }

        // Actifs uniquement par défaut
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'nom');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $students = $query->paginate($perPage);

        return response()->json($students);
    }

    /**
     * Afficher un élève avec toutes ses relations
     */
    public function show(string $id)
    {
        $student = StudentMP::with([
            'guardians',
            'enrollments.class',
            'enrollments.schoolYear',
            'grades.subject',
            'reportCards',
            'attendances',
            'history'
        ])->findOrFail($id);

        return response()->json($student);
    }

    /**
     * Créer un nouvel élève
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100|min:2',
            'prenoms' => 'required|string|max:150|min:2',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'nationalite' => 'nullable|string|max:50',
            'statut_inscription' => 'required|in:nouveau,ancien,transfert',
            'etablissement_origine' => 'nullable|string|max:200',
            'groupe_sanguin' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies' => 'nullable|string',
            'vaccinations' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Upload photo si présente
            if ($request->hasFile('photo_identite')) {
                $validated['photo_identite'] = $request->file('photo_identite')
                    ->store('students/photos', 'public');
            }

            // Upload extrait si présent
            if ($request->hasFile('extrait_naissance')) {
                $validated['extrait_naissance'] = $request->file('extrait_naissance')
                    ->store('students/documents', 'public');
            }

            $student = StudentMP::create($validated);

            // Journaliser
            AuditLog::log('student_created', StudentMP::class, $student->id, null, [
                'matricule' => $student->matricule,
                'nom' => $student->full_name,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Élève créé avec succès.',
                'student' => $student,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour un élève
     */
    public function update(Request $request, string $id)
    {
        $student = StudentMP::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:100|min:2',
            'prenoms' => 'sometimes|string|max:150|min:2',
            'date_naissance' => 'sometimes|date|before:today',
            'lieu_naissance' => 'sometimes|string|max:100',
            'sexe' => 'sometimes|in:M,F',
            'nationalite' => 'sometimes|string|max:50',
            'groupe_sanguin' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies' => 'nullable|string',
            'vaccinations' => 'nullable|array',
        ]);

        $oldValues = $student->only(array_keys($validated));

        // Gérer les uploads
        if ($request->hasFile('photo_identite')) {
            // Supprimer l'ancienne photo
            if ($student->photo_identite) {
                Storage::disk('public')->delete($student->photo_identite);
            }
            $validated['photo_identite'] = $request->file('photo_identite')
                ->store('students/photos', 'public');
        }

        if ($request->hasFile('extrait_naissance')) {
            if ($student->extrait_naissance) {
                Storage::disk('public')->delete($student->extrait_naissance);
            }
            $validated['extrait_naissance'] = $request->file('extrait_naissance')
                ->store('students/documents', 'public');
        }

        $student->update($validated);

        // Journaliser
        AuditLog::log('student_updated', StudentMP::class, $student->id, $oldValues, $validated);

        return response()->json([
            'message' => 'Élève mis à jour avec succès.',
            'student' => $student->fresh(),
        ]);
    }

    /**
     * Supprimer (soft delete) un élève
     */
    public function destroy(string $id)
    {
        $student = StudentMP::findOrFail($id);

        // Vérifier s'il n'a pas d'inscriptions actives
        $hasActiveEnrollment = $student->enrollments()
            ->where('school_year_id', SchoolYear::current()?->id)
            ->where('statut', 'validee')
            ->exists();

        if ($hasActiveEnrollment) {
            return response()->json([
                'message' => 'Impossible de supprimer un élève avec une inscription active.',
            ], 422);
        }

        $student->delete();

        AuditLog::log('student_deleted', StudentMP::class, $student->id);

        return response()->json([
            'message' => 'Élève supprimé avec succès.',
        ]);
    }

    /**
     * Historique complet d'un élève
     */
    public function history(string $id)
    {
        $student = StudentMP::findOrFail($id);

        $history = $student->history()
            ->with(['class', 'schoolYear'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'student' => $student->only(['id', 'matricule', 'nom', 'prenoms']),
            'history' => $history,
        ]);
    }

    /**
     * Bulletins d'un élève
     */
    public function reportCards(string $id)
    {
        $student = StudentMP::findOrFail($id);

        $reportCards = $student->reportCards()
            ->with(['class', 'schoolYear'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'student' => $student->only(['id', 'matricule', 'nom', 'prenoms']),
            'report_cards' => $reportCards,
        ]);
    }

    /**
     * Export des élèves en CSV/Excel
     */
    public function export(Request $request)
    {
        $query = StudentMP::with(['guardians', 'enrollments.class']);

        // Appliquer les mêmes filtres que index
        if ($request->has('niveau')) {
            $query->whereHas('enrollments.class', fn($q) => $q->where('niveau', $request->niveau));
        }

        if ($request->has('classe_id')) {
            $query->whereHas('enrollments', fn($q) => $q->where('class_id', $request->classe_id));
        }

        $students = $query->where('is_active', true)->get();

        // Format CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="eleves_mp_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($students) {
            $file = fopen('php://output', 'w');

            // En-têtes
            fputcsv($file, [
                'Matricule',
                'Nom',
                'Prénoms',
                'Date Naissance',
                'Lieu Naissance',
                'Sexe',
                'Classe',
                'Père - Nom',
                'Père - Téléphone',
                'Mère - Nom',
                'Mère - Téléphone',
            ]);

            foreach ($students as $student) {
                $enrollment = $student->currentEnrollment();
                $pere = $student->guardians->where('type', 'pere')->first();
                $mere = $student->guardians->where('type', 'mere')->first();

                fputcsv($file, [
                    $student->matricule,
                    $student->nom,
                    $student->prenoms,
                    $student->date_naissance->format('d/m/Y'),
                    $student->lieu_naissance,
                    $student->sexe,
                    $enrollment?->class?->nom ?? '-',
                    $pere?->nom_complet ?? '-',
                    $pere?->telephone_1 ?? '-',
                    $mere?->nom_complet ?? '-',
                    $mere?->telephone_1 ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import d'élèves depuis un fichier CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows); // Première ligne = en-têtes

        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $data = array_combine($header, $row);

                try {
                    // Créer l'élève
                    $student = StudentMP::create([
                        'nom' => $data['Nom'] ?? $data['nom'],
                        'prenoms' => $data['Prénoms'] ?? $data['prenoms'],
                        'date_naissance' => \Carbon\Carbon::createFromFormat('d/m/Y', $data['Date Naissance'] ?? $data['date_naissance']),
                        'lieu_naissance' => $data['Lieu Naissance'] ?? $data['lieu_naissance'],
                        'sexe' => strtoupper($data['Sexe'] ?? $data['sexe']),
                        'nationalite' => $data['Nationalité'] ?? 'Burkinabè',
                        'statut_inscription' => 'nouveau',
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'ligne' => $index + 2,
                        'erreur' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "{$imported} élève(s) importé(s) avec succès.",
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Statistiques des élèves
     */
    public function stats(Request $request)
    {
        $schoolYear = SchoolYear::current();

        // Total élèves actifs
        $totalActive = StudentMP::where('is_active', true)->count();

        // Par sexe
        $bySex = StudentMP::where('is_active', true)
            ->select('sexe', DB::raw('count(*) as count'))
            ->groupBy('sexe')
            ->pluck('count', 'sexe');

        // Par niveau
        $byLevel = DB::connection('school_mp')
            ->table('enrollments_mp')
            ->join('classes_mp', 'enrollments_mp.class_id', '=', 'classes_mp.id')
            ->where('enrollments_mp.school_year_id', $schoolYear?->id)
            ->where('enrollments_mp.statut', 'validee')
            ->select('classes_mp.niveau', DB::raw('count(*) as count'))
            ->groupBy('classes_mp.niveau')
            ->pluck('count', 'niveau');

        // Nouveaux vs anciens
        $byStatus = StudentMP::where('is_active', true)
            ->select('statut_inscription', DB::raw('count(*) as count'))
            ->groupBy('statut_inscription')
            ->pluck('count', 'statut_inscription');

        return response()->json([
            'total_active' => $totalActive,
            'by_sex' => $bySex,
            'by_level' => $byLevel,
            'by_status' => $byStatus,
        ]);
    }
}
