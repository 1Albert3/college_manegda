<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MP\GradeMP;
use App\Models\MP\StudentMP;
use App\Models\MP\SubjectMP;
use App\Models\MP\ClassMP;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur des notes Maternelle/Primaire
 * 
 * Fonctionnalités:
 * - Saisie individuelle et en masse
 * - Publication (verrouillage)
 * - Statistiques par classe/matière
 * - Contrôle des droits enseignant
 */
class GradeMPController extends Controller
{
    /**
     * Liste des notes avec filtres
     */
    public function index(Request $request)
    {
        $query = GradeMP::with(['student', 'subject', 'class']);

        // Filtres obligatoires pour limiter les données
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('trimestre')) {
            $query->where('trimestre', $request->trimestre);
        }

        if ($request->has('type_evaluation')) {
            $query->where('type_evaluation', $request->type_evaluation);
        }

        // Année scolaire courante par défaut
        $schoolYearId = $request->get('school_year_id', SchoolYear::current()?->id);
        if ($schoolYearId) {
            $query->where('school_year_id', $schoolYearId);
        }

        // Notes publiées uniquement pour les parents/élèves
        $user = $request->user();
        if ($user && in_array($user->role, ['parent', 'eleve'])) {
            $query->where('is_published', true);
        }

        $grades = $query->orderByDesc('date_evaluation')->paginate($request->per_page ?? 50);

        return response()->json($grades);
    }

    /**
     * Afficher une note
     */
    public function show(string $id)
    {
        $grade = GradeMP::with(['student', 'subject', 'class', 'schoolYear'])->findOrFail($id);
        return response()->json($grade);
    }

    /**
     * Créer une note (saisie individuelle)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:school_mp.students_mp,id',
            'subject_id' => 'required|uuid|exists:school_mp.subjects_mp,id',
            'class_id' => 'required|uuid|exists:school_mp.classes_mp,id',
            'school_year_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
            'type_evaluation' => 'required|in:IO,DV,CP,TP',
            'note_obtenue' => 'required|numeric|min:0',
            'date_evaluation' => 'required|date',
            'commentaire' => 'nullable|string|max:500',
        ]);

        // Définir le barème selon le type
        $baremes = [
            'IO' => 10, // Interrogation Orale /10
            'DV' => 20, // Devoir /20
            'CP' => 100, // Composition /100
            'TP' => 20,  // TP /20
        ];

        $validated['note_sur'] = $baremes[$validated['type_evaluation']];

        // Vérifier que la note ne dépasse pas le barème
        if ($validated['note_obtenue'] > $validated['note_sur']) {
            throw ValidationException::withMessages([
                'note_obtenue' => ["La note ne peut pas dépasser {$validated['note_sur']}"],
            ]);
        }

        // Calculer la note sur 20
        $validated['note_sur_20'] = round(($validated['note_obtenue'] / $validated['note_sur']) * 20, 2);
        $validated['recorded_by'] = $request->user()->id;

        $grade = GradeMP::create($validated);

        AuditLog::log('grade_created', GradeMP::class, $grade->id, null, [
            'student_id' => $grade->student_id,
            'subject_id' => $grade->subject_id,
            'note' => $grade->note_obtenue,
        ]);

        return response()->json([
            'message' => 'Note enregistrée avec succès.',
            'grade' => $grade->load(['student', 'subject']),
        ], 201);
    }

    /**
     * Saisie en masse des notes (pour une évaluation)
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|uuid|exists:school_mp.subjects_mp,id',
            'class_id' => 'required|uuid|exists:school_mp.classes_mp,id',
            'school_year_id' => 'required',
            'trimestre' => 'required|in:1,2,3',
            'type_evaluation' => 'required|in:IO,DV,CP,TP',
            'date_evaluation' => 'required|date',
            'notes' => 'required|array|min:1',
            'notes.*.student_id' => 'required|uuid|exists:school_mp.students_mp,id',
            'notes.*.note_obtenue' => 'required|numeric|min:0',
            'notes.*.commentaire' => 'nullable|string|max:500',
            'publish' => 'sometimes|boolean',
        ]);

        if (($validated['school_year_id'] ?? null) === 'current') {
            $currentSchoolYearId = SchoolYear::current()?->id;
            if (!$currentSchoolYearId) {
                return response()->json([
                    'message' => 'Aucune année scolaire courante n\'est définie.',
                ], 422);
            }
            $validated['school_year_id'] = $currentSchoolYearId;
        }

        if (!is_string($validated['school_year_id']) || !preg_match('/^[0-9a-fA-F-]{36}$/', $validated['school_year_id'])) {
            return response()->json([
                'message' => 'school_year_id invalide.',
            ], 422);
        }

        $baremes = ['IO' => 10, 'DV' => 20, 'CP' => 100, 'TP' => 20];
        $noteSur = $baremes[$validated['type_evaluation']];

        $created = 0;
        $errors = [];
        $publish = (bool) ($validated['publish'] ?? false);

        DB::beginTransaction();
        try {
            foreach ($validated['notes'] as $noteData) {
                // Vérifier que la note ne dépasse pas le barème
                if ($noteData['note_obtenue'] > $noteSur) {
                    $errors[] = [
                        'student_id' => $noteData['student_id'],
                        'error' => "Note {$noteData['note_obtenue']} dépasse le barème {$noteSur}",
                    ];
                    continue;
                }

                GradeMP::create([
                    'student_id' => $noteData['student_id'],
                    'subject_id' => $validated['subject_id'],
                    'class_id' => $validated['class_id'],
                    'school_year_id' => $validated['school_year_id'],
                    'trimestre' => $validated['trimestre'],
                    'type_evaluation' => $validated['type_evaluation'],
                    'note_sur' => $noteSur,
                    'note_obtenue' => $noteData['note_obtenue'],
                    'note_sur_20' => round(($noteData['note_obtenue'] / $noteSur) * 20, 2),
                    'date_evaluation' => $validated['date_evaluation'],
                    'commentaire' => $noteData['commentaire'] ?? null,
                    'recorded_by' => $request->user()->id,
                    'is_published' => $publish,
                    'published_at' => $publish ? now() : null,
                ]);

                $created++;
            }

            DB::commit();

            AuditLog::log('grades_bulk_created', GradeMP::class, null, null, [
                'class_id' => $validated['class_id'],
                'subject_id' => $validated['subject_id'],
                'count' => $created,
            ]);

            return response()->json([
                'message' => "{$created} note(s) enregistrée(s) avec succès.",
                'created' => $created,
                'published' => $publish ? $created : 0,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Modifier une note (si non publiée)
     */
    public function update(Request $request, string $id)
    {
        $grade = GradeMP::findOrFail($id);

        // Vérifier que la note n'est pas publiée
        if ($grade->is_published) {
            return response()->json([
                'message' => 'Impossible de modifier une note publiée.',
            ], 422);
        }

        $validated = $request->validate([
            'note_obtenue' => 'sometimes|numeric|min:0',
            'commentaire' => 'nullable|string|max:500',
            'date_evaluation' => 'sometimes|date',
        ]);

        $oldValues = $grade->only(array_keys($validated));

        if (isset($validated['note_obtenue'])) {
            if ($validated['note_obtenue'] > $grade->note_sur) {
                throw ValidationException::withMessages([
                    'note_obtenue' => ["La note ne peut pas dépasser {$grade->note_sur}"],
                ]);
            }
            $validated['note_sur_20'] = round(($validated['note_obtenue'] / $grade->note_sur) * 20, 2);
        }

        $grade->update($validated);

        AuditLog::log('grade_updated', GradeMP::class, $grade->id, $oldValues, $validated);

        return response()->json([
            'message' => 'Note mise à jour avec succès.',
            'grade' => $grade->fresh(['student', 'subject']),
        ]);
    }

    /**
     * Supprimer une note (si non publiée)
     */
    public function destroy(string $id)
    {
        $grade = GradeMP::findOrFail($id);

        if ($grade->is_published) {
            return response()->json([
                'message' => 'Impossible de supprimer une note publiée.',
            ], 422);
        }

        $grade->delete();

        AuditLog::log('grade_deleted', GradeMP::class, $id);

        return response()->json([
            'message' => 'Note supprimée avec succès.',
        ]);
    }

    /**
     * Publier les notes (verrouillage - visible aux parents)
     */
    public function publish(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid',
            'subject_id' => 'sometimes|uuid',
            'trimestre' => 'required|in:1,2,3',
            'school_year_id' => 'required|uuid',
        ]);

        $query = GradeMP::where('class_id', $validated['class_id'])
            ->where('trimestre', $validated['trimestre'])
            ->where('school_year_id', $validated['school_year_id'])
            ->where('is_published', false);

        if (isset($validated['subject_id'])) {
            $query->where('subject_id', $validated['subject_id']);
        }

        $count = $query->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        AuditLog::log('grades_published', GradeMP::class, null, null, [
            'class_id' => $validated['class_id'],
            'trimestre' => $validated['trimestre'],
            'count' => $count,
        ]);

        return response()->json([
            'message' => "{$count} note(s) publiée(s) avec succès.",
            'published' => $count,
        ]);
    }

    /**
     * Statistiques des notes par classe
     */
    public function statsClass(Request $request, string $classId)
    {
        $request->validate([
            'trimestre' => 'required|in:1,2,3',
            'school_year_id' => 'required|uuid',
        ]);

        $class = ClassMP::findOrFail($classId);

        // Notes par matière
        $stats = GradeMP::where('class_id', $classId)
            ->where('trimestre', $request->trimestre)
            ->where('school_year_id', $request->school_year_id)
            ->select(
                'subject_id',
                DB::raw('AVG(note_sur_20) as moyenne'),
                DB::raw('MIN(note_sur_20) as min'),
                DB::raw('MAX(note_sur_20) as max'),
                DB::raw('COUNT(*) as total_notes'),
                DB::raw('COUNT(DISTINCT student_id) as nb_eleves')
            )
            ->groupBy('subject_id')
            ->with('subject:id,nom,code')
            ->get();

        // Moyenne générale de la classe
        $moyenneClasse = GradeMP::where('class_id', $classId)
            ->where('trimestre', $request->trimestre)
            ->where('school_year_id', $request->school_year_id)
            ->avg('note_sur_20');

        return response()->json([
            'class' => $class->only(['id', 'nom', 'niveau']),
            'trimestre' => $request->trimestre,
            'moyenne_generale' => round($moyenneClasse ?? 0, 2),
            'stats_matieres' => $stats,
        ]);
    }

    /**
     * Notes d'un élève pour un trimestre
     */
    public function studentGrades(Request $request, string $studentId)
    {
        $request->validate([
            'trimestre' => 'required|in:1,2,3',
            'school_year_id' => 'required|uuid',
        ]);

        $student = StudentMP::findOrFail($studentId);

        $grades = GradeMP::where('student_id', $studentId)
            ->where('trimestre', $request->trimestre)
            ->where('school_year_id', $request->school_year_id)
            ->with(['subject:id,nom,code,coefficient'])
            ->orderBy('subject_id')
            ->orderBy('date_evaluation')
            ->get();

        // Grouper par matière
        $bySubject = $grades->groupBy('subject_id')->map(function ($subjectGrades) {
            $subject = $subjectGrades->first()->subject;
            return [
                'subject' => $subject,
                'grades' => $subjectGrades,
                'moyenne' => round($subjectGrades->avg('note_sur_20'), 2),
                'count' => $subjectGrades->count(),
            ];
        });

        // Moyenne générale
        $moyenneGenerale = $grades->avg('note_sur_20');

        return response()->json([
            'student' => $student->only(['id', 'matricule', 'nom', 'prenoms']),
            'trimestre' => $request->trimestre,
            'by_subject' => $bySubject->values(),
            'moyenne_generale' => round($moyenneGenerale ?? 0, 2),
            'total_notes' => $grades->count(),
        ]);
    }
    /**
     * Liste des matières (pour liste déroulante)
     */
    public function subjects(Request $request)
    {
        $query = SubjectMP::active()->orderBy('nom');

        if ($request->has('niveau')) {
            $query->forLevel($request->niveau);
        }

        return response()->json($query->get());
    }
}
