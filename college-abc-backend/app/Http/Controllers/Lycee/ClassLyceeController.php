<?php

namespace App\Http\Controllers\Lycee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Lycee\ClassLycee;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Contrôleur des classes Lycée
 * 
 * Gestion des classes de 2nde, 1ère, Tle
 * Intègre la gestion des séries (A, C, D, etc.)
 */
class ClassLyceeController extends Controller
{
    /**
     * Liste des classes Lycée
     */
    public function index(Request $request)
    {
        $query = ClassLycee::with(['profPrincipal', 'schoolYear']);

        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        } else {
            $query->currentYear();
        }

        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        if ($request->has('serie')) {
            $query->where('serie', $request->serie);
        }

        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Tri intelligent: 2nde d'abord, puis 1ère, puis Tle
        $query->orderByRaw("FIELD(niveau, '2nde', '1ere', 'Tle')")
            ->orderBy('serie') // A, C, D...
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
            'niveau' => ['required', Rule::in(ClassLycee::NIVEAUX)],
            'serie' => ['nullable'], // Simplified
            'nom' => 'required|string|max:20',

            'seuil_minimum' => ['nullable', 'integer'], // Removed class_threshold
            'seuil_maximum' => ['nullable', 'integer', 'gte:seuil_minimum'], // Removed class_threshold

            'salle' => 'nullable|string|max:50',
            'prof_principal_id' => 'nullable|uuid',
        ]);

        // Default thresholds
        $validated['seuil_minimum'] = $validated['seuil_minimum'] ?? 15;
        $validated['seuil_maximum'] = $validated['seuil_maximum'] ?? 40;

        // Unicité (Année + Niveau + Série + Nom)
        $exists = ClassLycee::where('school_year_id', $validated['school_year_id'])
            ->where('niveau', $validated['niveau'])
            ->where('serie', $validated['serie']) // Null safe ? check laravel behavior or explicit check
            ->where('nom', $validated['nom'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Une classe avec ces caractéristiques existe déjà.',
            ], 422);
        }

        $class = ClassLycee::create($validated);

        AuditLog::log('class_lycee_created', ClassLycee::class, $class->id);

        return response()->json([
            'message' => 'Classe Lycée créée avec succès.',
            'class' => $class
        ], 201);
    }

    /**
     * Update
     */
    public function update(Request $request, string $id)
    {
        $class = ClassLycee::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:20',
            'serie' => ['nullable', Rule::in(ClassLycee::SERIES)],
            'seuil_minimum' => ['sometimes', 'integer', 'class_threshold'],
            'seuil_maximum' => ['sometimes', 'integer', 'class_threshold'],
            'salle' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        // Vérif cohérence min/max
        $newMin = $validated['seuil_minimum'] ?? $class->seuil_minimum;
        $newMax = $validated['seuil_maximum'] ?? $class->seuil_maximum;

        if ($newMin > $newMax) {
            return response()->json(['message' => 'Min > Max impossible'], 422);
        }

        if ($newMax < $class->effectif_actuel) {
            return response()->json(['message' => 'Capacité inférieure à effectif actuel'], 422);
        }

        $class->update($validated);

        return response()->json(['message' => 'Mise à jour OK', 'class' => $class]);
    }

    /**
     * Show
     */
    public function show($id)
    {
        return response()->json(ClassLycee::with('schoolYear')->findOrFail($id));
    }

    /**
     * Liste des élèves d'une classe
     */
    public function students(string $id)
    {
        $class = ClassLycee::findOrFail($id);
        // Use the defined relationship which handles the join through enrollments
        $students = $class->students()
            ->where('students_lycee.is_active', true)
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        return response()->json([
            'class' => $class,
            'students' => $students
        ]);
    }

    /**
     * Delete
     */
    public function destroy($id)
    {
        $class = ClassLycee::findOrFail($id);
        if ($class->effectif_actuel > 0) {
            return response()->json(['message' => 'Classe non vide'], 422);
        }
        $class->delete();
        return response()->json(['message' => 'Classe supprimée']);
    }

    // --- ASSIGNATION PROFS (Added on User Request) ---

    public function assignments($id)
    {
        // Join teacher_subject_assignments with subjects_lycee and teachers_lycee
        $assignments = DB::connection('school_lycee')->table('teacher_subject_assignments')
            ->join('subjects_lycee', 'teacher_subject_assignments.subject_id', '=', 'subjects_lycee.id')
            ->join('teachers_lycee', 'teacher_subject_assignments.teacher_id', '=', 'teachers_lycee.id')
            ->select(
                'teacher_subject_assignments.id as assignment_id',
                'subjects_lycee.nom as subject_name',
                'teacher_subject_assignments.teacher_id',
                'teacher_subject_assignments.subject_id',
                'teachers_lycee.user_id'
            )
            ->where('teacher_subject_assignments.class_id', $id)
            ->get();

        // Enrichir avec les infos User (nom/prenom) depuis school_core
        $userIds = $assignments->pluck('user_id')->unique();
        if ($userIds->isNotEmpty()) {
            $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
            foreach ($assignments as $a) {
                $u = $users[$a->user_id] ?? null;
                $a->teacher_name = $u ? "$u->first_name $u->last_name" : "Inconnu";
                $a->teacher_email = $u ? $u->email : "";
            }
        } else {
            foreach ($assignments as $a) {
                $a->teacher_name = "Inconnu";
                $a->teacher_email = "";
            }
        }

        return response()->json($assignments);
    }

    public function assignTeacher(Request $request, $id)
    {
        $request->validate([
            'teacher_id' => 'required',
            'subject_id' => 'required',
            'school_year_id' => 'required'
        ]);

        // Get or create teacher entry in teachers_lycee (user_id -> teachers_lycee.id mapping)
        $teacherLycee = DB::connection('school_lycee')->table('teachers_lycee')->where('user_id', $request->teacher_id)->first();

        if (!$teacherLycee) {
            $teacherLyceeId = \Illuminate\Support\Str::uuid()->toString();
            DB::connection('school_lycee')->table('teachers_lycee')->insert([
                'id' => $teacherLyceeId,
                'user_id' => $request->teacher_id,
                'matricule' => 'T-LYC-' . substr($request->teacher_id, 0, 8),
                'statut' => 'actif',
                'type_contrat' => 'permanent',
                'date_embauche' => now()->toDateString(),
                'anciennete_annees' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $teacherLyceeId = $teacherLycee->id;
        }

        // Check for duplicates using the correct teacher_id (teachers_lycee.id)
        $exists = DB::connection('school_lycee')->table('teacher_subject_assignments')
            ->where('class_id', $id)
            ->where('teacher_id', $teacherLyceeId)
            ->where('subject_id', $request->subject_id)
            ->where('school_year_id', $request->school_year_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Déjà assigné'], 422);
        }

        DB::connection('school_lycee')->table('teacher_subject_assignments')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'class_id' => $id,
            'teacher_id' => $teacherLyceeId,
            'subject_id' => $request->subject_id,
            'school_year_id' => $request->school_year_id,
            'heures_par_semaine' => 4,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Assignation réussie']);
    }

    public function removeAssignment($id, $assignmentId)
    {
        DB::connection('school_lycee')->table('teacher_subject_assignments')
            ->where('id', $assignmentId)
            ->delete();
        return response()->json(['message' => 'Assignation supprimée']);
    }

    public function availableResources()
    {
        // Teachers: Users with role 'enseignant'
        $teachers = \App\Models\User::where('role', 'enseignant')->orWhere('role', 'teacher')->get()->map(function ($u) {
            return ['id' => $u->id, 'name' => "$u->first_name $u->last_name", 'email' => $u->email];
        });

        // Subjects Lycée
        $subjects = DB::connection('school_lycee')->table('subjects_lycee')->orderBy('nom')->get()->map(function ($s) {
            return ['id' => $s->id, 'name' => $s->nom, 'code' => $s->code];
        });

        return response()->json([
            'teachers' => $teachers,
            'subjects' => $subjects
        ]);
    }
}
