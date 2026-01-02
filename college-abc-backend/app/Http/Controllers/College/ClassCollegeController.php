<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\College\ClassCollege;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contrôleur des classes Collège
 * 
 * Gestion des niveaux 6ème à 3ème
 * Validation stricte des effectifs (15-40/50 selon cycle, mais uniformisons le standard 15-40 si besoin)
 * ou respectons les constantes du modèle (30-50).
 */
class ClassCollegeController extends Controller
{
    /**
     * Liste des classes Collège
     */
    public function index(Request $request)
    {
        $query = ClassCollege::with(['profPrincipal', 'schoolYear']);

        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        } else {
            $query->currentYear();
        }

        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Tri: 6eme, 5eme, 4eme, 3eme
        $query->orderByRaw("FIELD(niveau, '6eme', '5eme', '4eme', '3eme')")
            ->orderBy('nom');

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    /**
     * Store (Création)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|uuid',
            'niveau' => ['required', Rule::in(ClassCollege::NIVEAUX)],
            'nom' => 'required|string|max:20', // Ex: A, B, 1, 2

            // Validation Stricte via le provider (15-40) OU seuils modèles ?
            // Le Provider impose 15-40 via 'class_threshold'.
            // Si le collège a besoin de plus (ex: 50), il faut adapter le validateur ou ne pas l'utiliser ici.
            // Pour l'uniformité "Zero Erreur" demandée, on applique le standard ministère ou on override si spécifié.
            'seuil_minimum' => ['nullable', 'integer'],
            'seuil_maximum' => ['nullable', 'integer', 'gte:seuil_minimum'],

            'salle' => 'nullable|string|max:50',
            'prof_principal_id' => 'nullable|uuid',
        ]);

        $validated['seuil_minimum'] = $validated['seuil_minimum'] ?? 30; // Valeur modèle
        $validated['seuil_maximum'] = $validated['seuil_maximum'] ?? 40; // Max autorisé par validateur global (sinon 50)

        // Unicité
        $exists = ClassCollege::where('school_year_id', $validated['school_year_id'])
            ->where('niveau', $validated['niveau'])
            ->where('nom', $validated['nom'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Cette classe existe déjà.'], 422);
        }

        $class = ClassCollege::create($validated);
        // Log absent ? on peut ajouter AuditLog si le modèle existe en core

        return response()->json(['message' => 'Classe Collège créée.', 'class' => $class], 201);
    }

    /**
     * Update
     */
    public function update(Request $request, string $id)
    {
        $class = ClassCollege::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:20',
            'seuil_minimum' => ['sometimes', 'integer', 'class_threshold'],
            'seuil_maximum' => ['sometimes', 'integer', 'class_threshold'],
            'salle' => 'nullable|string|max:50',
            'prof_principal_id' => 'nullable|uuid',
            'is_active' => 'sometimes|boolean',
        ]);

        $newMin = $validated['seuil_minimum'] ?? $class->seuil_minimum;
        $newMax = $validated['seuil_maximum'] ?? $class->seuil_maximum;

        if ($newMin > $newMax) {
            return response()->json(['message' => 'Min > Max impossible'], 422);
        }

        $class->update($validated);

        return response()->json(['message' => 'Mise à jour OK', 'class' => $class]);
    }

    public function show($id)
    {
        return response()->json(ClassCollege::with(['schoolYear', 'profPrincipal'])->findOrFail($id));
    }

    /**
     * Liste des élèves d'une classe (via Enrollments)
     */
    public function students(string $id)
    {
        $class = ClassCollege::findOrFail($id);
        $schoolYearId = \App\Models\SchoolYear::current()->id;

        // Récupérer les inscriptions pour cette classe et cette année
        $enrollments = \App\Models\College\EnrollmentCollege::where('class_id', $id)
            ->where('school_year_id', $schoolYearId)
            ->where('statut', '!=', 'abandon') // Exemple de filtre
            ->with(['student' => function ($q) {
                $q->orderBy('nom')->orderBy('prenoms');
            }])
            ->get();

        // Extraire les étudiants des inscriptions
        $students = $enrollments->map(function ($enrollment) {
            $student = $enrollment->student;
            // On peut ajouter des infos de l'inscription si nécessaire
            if ($student) {
                $student->enrollment_id = $enrollment->id;
                $student->matricule = $student->matricule ?? $enrollment->matricule; // Fallback
            }
            return $student;
        })->filter()->values();

        return response()->json([
            'class' => $class,
            'students' => $students
        ]);
    }

    public function destroy($id)
    {
        $class = ClassCollege::findOrFail($id);
        if ($class->effectif_actuel > 0) {
            return response()->json(['message' => 'Classe non vide'], 422);
        }
        $class->delete();
        return response()->json(['message' => 'Classe supprimée']);
    }
}
