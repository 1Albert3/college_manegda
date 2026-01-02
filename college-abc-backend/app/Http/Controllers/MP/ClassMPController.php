<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MP\ClassMP;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur des classes Maternelle/Primaire
 * 
 * Gestion complète des classes:
 * - CRUD
 * - Gestion des effectifs et seuils (STRICT: 15-40 élèves)
 * - Attribution des enseignants
 * - Statistiques
 */
class ClassMPController extends Controller
{
    /**
     * Liste des classe avec filtres et pagination
     */
    public function index(Request $request)
    {
        $query = ClassMP::with(['teacher', 'schoolYear']);

        // Filtres
        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        } else {
            // Année courante par défaut
            $query->currentYear();
        }

        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        if ($request->has('cycle')) {
            $query->byCycle($request->cycle);
        }

        // Actives uniquement par défaut
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Tri
        $query->orderByRaw("FIELD(niveau, 'PS', 'MS', 'GS', 'CP', 'CE1', 'CE2', 'CM1', 'CM2')")
            ->orderBy('nom');

        $classes = $query->paginate($request->per_page ?? 50);

        return response()->json($classes);
    }

    /**
     * Afficher une classe avec ses relations
     */
    public function show(string $id)
    {
        $class = ClassMP::with([
            'teacher',
            'schoolYear',
            'enrollments' => fn($q) => $q->where('statut', 'validee'),
            'enrollments.student'
        ])->findOrFail($id);

        return response()->json($class);
    }

    /**
     * Créer une nouvelle classe
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|uuid',
            'niveau' => 'required|in:PS,MS,GS,CP,CP1,CP2,CE1,CE2,CM1,CM2',
            'nom' => 'required|string|max:20',
            'seuil_minimum' => ['nullable', 'integer'],
            'seuil_maximum' => ['nullable', 'integer', 'gte:seuil_minimum'],
            'salle' => 'nullable|string|max:50',
            'teacher_id' => 'nullable|uuid|exists:school_mp.teachers_mp,id',
        ]);

        // Vérifier l'unicité du nom pour ce niveau/année
        $exists = ClassMP::where('school_year_id', $validated['school_year_id'])
            ->where('niveau', $validated['niveau'])
            ->where('nom', $validated['nom'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Une classe avec ce nom existe déjà pour ce niveau et cette année.',
            ], 422);
        }

        // Valeurs par défaut conformes si non fournies
        $validated['seuil_minimum'] = $validated['seuil_minimum'] ?? 15;
        $validated['seuil_maximum'] = $validated['seuil_maximum'] ?? 40;

        $class = ClassMP::create($validated);

        AuditLog::log('class_created', ClassMP::class, $class->id, null, [
            'niveau' => $class->niveau,
            'nom' => $class->nom,
        ]);

        return response()->json([
            'message' => 'Classe créée avec succès.',
            'class' => $class->load('teacher'),
        ], 201);
    }

    /**
     * Mettre à jour une classe
     */
    public function update(Request $request, string $id)
    {
        $class = ClassMP::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:20',
            // Validation stricte du seuil de classe via SchoolDatabaseProvider
            'seuil_minimum' => ['sometimes', 'integer', 'class_threshold'],
            'seuil_maximum' => ['sometimes', 'integer', 'class_threshold'],
            'salle' => 'nullable|string|max:50',
            'teacher_id' => 'nullable|uuid|exists:school_mp.teachers_mp,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // Validation croisée min/max si les deux sont présents ou mélangés avec l'existant
        $newMin = $validated['seuil_minimum'] ?? $class->seuil_minimum;
        $newMax = $validated['seuil_maximum'] ?? $class->seuil_maximum;

        if ($newMin > $newMax) {
            return response()->json([
                'message' => "Le seuil minimum ($newMin) ne peut pas dépasser le seuil maximum ($newMax).",
            ], 422);
        }

        // Vérification de la capacité par rapport à l'effectif actuel
        if ($newMax < $class->effectif_actuel) {
            return response()->json([
                'message' => "Le seuil maximum ($newMax) ne peut pas être inférieur à l'effectif actuel ({$class->effectif_actuel}).",
            ], 422);
        }

        $oldValues = $class->only(array_keys($validated));
        $class->update($validated);

        AuditLog::log('class_updated', ClassMP::class, $class->id, $oldValues, $validated);

        return response()->json([
            'message' => 'Classe mise à jour avec succès.',
            'class' => $class->fresh(['teacher']),
        ]);
    }

    /**
     * Supprimer une classe
     */
    public function destroy(string $id)
    {
        $class = ClassMP::findOrFail($id);

        // Vérifier qu'il n'y a pas d'élèves inscrits
        if ($class->effectif_actuel > 0) {
            return response()->json([
                'message' => "Impossible de supprimer une classe avec {$class->effectif_actuel} élève(s) inscrit(s).",
            ], 422);
        }

        $class->delete();

        AuditLog::log('class_deleted', ClassMP::class, $id);

        return response()->json([
            'message' => 'Classe supprimée avec succès.',
        ]);
    }

    /**
     * Liste des élèves d'une classe
     */
    public function students(string $id)
    {
        $class = ClassMP::findOrFail($id);

        $students = $class->students()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        return response()->json([
            'class' => $class->only(['id', 'niveau', 'nom', 'effectif_actuel']),
            'students' => $students,
        ]);
    }

    /**
     * Assigner un enseignant à la classe
     */
    public function assignTeacher(Request $request, string $id)
    {
        $class = ClassMP::findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'required|uuid|exists:school_mp.teachers_mp,id',
        ]);

        $oldTeacherId = $class->teacher_id;
        $class->update(['teacher_id' => $validated['teacher_id']]);

        AuditLog::log('class_teacher_assigned', ClassMP::class, $class->id, [
            'old_teacher_id' => $oldTeacherId,
        ], [
            'new_teacher_id' => $validated['teacher_id'],
        ]);

        return response()->json([
            'message' => 'Enseignant assigné avec succès.',
            'class' => $class->load('teacher'),
        ]);
    }

    /**
     * Statistiques des classes
     */
    public function stats(Request $request)
    {
        $schoolYearId = $request->get('school_year_id', SchoolYear::current()?->id);

        $classes = ClassMP::where('school_year_id', $schoolYearId)
            ->where('is_active', true)
            ->get();

        // Par cycle
        $byCycle = $classes->groupBy(function ($class) {
            return $class->cycle;
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'effectif_total' => $group->sum('effectif_actuel'),
                'capacite_totale' => $group->sum('seuil_maximum'),
            ];
        });

        // Par niveau
        $byLevel = $classes->groupBy('niveau')->map(function ($group) {
            return [
                'count' => $group->count(),
                'effectif_total' => $group->sum('effectif_actuel'),
                'effectif_moyen' => round($group->avg('effectif_actuel'), 1),
            ];
        });

        // Classes en alerte
        $alertClasses = $classes->filter(function ($class) {
            return $class->fill_rate >= 90;
        })->values();

        return response()->json([
            'total_classes' => $classes->count(),
            'total_effectif' => $classes->sum('effectif_actuel'),
            'total_capacite' => $classes->sum('seuil_maximum'),
            'taux_remplissage_global' => $classes->sum('seuil_maximum') > 0
                ? round(($classes->sum('effectif_actuel') / $classes->sum('seuil_maximum')) * 100, 1)
                : 0,
            'by_cycle' => $byCycle,
            'by_level' => $byLevel,
            'classes_en_alerte' => $alertClasses,
        ]);
    }

    /**
     * Dupliquer les classes d'une année vers une nouvelle année
     */
    public function duplicate(Request $request)
    {
        $validated = $request->validate([
            'source_year_id' => 'required|uuid|exists:school_core.school_years,id',
            'target_year_id' => 'required|uuid|exists:school_core.school_years,id',
        ]);

        // Récupérer les classes source
        $sourceClasses = ClassMP::where('school_year_id', $validated['source_year_id'])
            ->where('is_active', true)
            ->get();

        if ($sourceClasses->isEmpty()) {
            return response()->json([
                'message' => 'Aucune classe à dupliquer dans l\'année source.',
            ], 422);
        }

        $created = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($sourceClasses as $sourceClass) {
                // Vérifier si existe déjà
                $exists = ClassMP::where('school_year_id', $validated['target_year_id'])
                    ->where('niveau', $sourceClass->niveau)
                    ->where('nom', $sourceClass->nom)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Créer la nouvelle classe
                ClassMP::create([
                    'school_year_id' => $validated['target_year_id'],
                    'niveau' => $sourceClass->niveau,
                    'nom' => $sourceClass->nom,
                    'seuil_minimum' => $sourceClass->seuil_minimum,
                    'seuil_maximum' => $sourceClass->seuil_maximum,
                    'effectif_actuel' => 0,
                    'salle' => $sourceClass->salle,
                    'teacher_id' => null, // Ne pas copier l'enseignant
                    'is_active' => true,
                ]);

                $created++;
            }

            DB::commit();

            AuditLog::log('classes_duplicated', null, null, null, [
                'source_year_id' => $validated['source_year_id'],
                'target_year_id' => $validated['target_year_id'],
                'created' => $created,
                'skipped' => $skipped,
            ]);

            return response()->json([
                'message' => "{$created} classe(s) créée(s), {$skipped} ignorée(s) (déjà existantes).",
                'created' => $created,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
