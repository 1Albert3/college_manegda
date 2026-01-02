<?php

namespace App\Http\Controllers\Discipline;

use App\Http\Controllers\Controller;
use App\Models\College\DisciplineIncident;
use App\Models\College\DisciplineSanction;
use App\Models\AuditLog;
use App\Models\SchoolYear;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur de Discipline
 * 
 * Gestion des incidents et sanctions disciplinaires
 */
class DisciplineController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ==========================================
    // INCIDENTS
    // ==========================================

    /**
     * Liste des incidents
     */
    public function incidents(Request $request)
    {
        $query = DisciplineIncident::with(['student:id,nom,prenoms,matricule', 'class:id,niveau,nom']);

        // Filtres
        if ($request->has('student_id')) {
            $query->forStudent($request->student_id);
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('gravite')) {
            $query->byGravity($request->gravite);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        } else {
            // Par défaut, les non traités en premier
            $query->orderByRaw("FIELD(statut, 'signale', 'en_cours', 'traite', 'classe')");
        }

        $query->orderByDesc('date_incident');

        $incidents = $query->paginate($request->per_page ?? 20);

        return response()->json($incidents);
    }

    /**
     * Signaler un incident
     */
    public function createIncident(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid',
            'class_id' => 'required|uuid',
            'date_incident' => 'required|date|before_or_equal:today',
            'heure_incident' => 'nullable|date_format:H:i',
            'lieu' => 'required|string|max:100',
            'type' => 'required|in:comportement,violence,retards_repetes,absences,tricherie,degradation,insolence,tenue,autre',
            'gravite' => 'required|in:mineure,moyenne,grave,tres_grave',
            'description' => 'required|string|min:20',
            'circonstances' => 'nullable|string',
            'temoins' => 'nullable|array',
        ]);

        $validated['school_year_id'] = SchoolYear::current()->id;
        $validated['signale_par'] = $request->user()->id;
        $validated['statut'] = 'signale';

        $incident = DisciplineIncident::create($validated);

        AuditLog::log('discipline_incident_created', DisciplineIncident::class, $incident->id, null, [
            'student_id' => $incident->student_id,
            'type' => $incident->type,
            'gravite' => $incident->gravite,
        ]);

        // Notifier la direction si incident grave
        if (in_array($validated['gravite'], ['grave', 'tres_grave'])) {
            // TODO: Notification direction
        }

        return response()->json([
            'message' => 'Incident signalé avec succès.',
            'incident' => $incident->load(['student', 'class']),
        ], 201);
    }

    /**
     * Détails d'un incident
     */
    public function showIncident(string $id)
    {
        $incident = DisciplineIncident::with([
            'student:id,nom,prenoms,matricule,photo_url',
            'class:id,niveau,nom',
            'sanctions'
        ])->findOrFail($id);

        return response()->json($incident);
    }

    /**
     * Mettre à jour le statut d'un incident
     */
    public function updateIncidentStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'statut' => 'required|in:signale,en_cours,traite,classe',
        ]);

        $incident = DisciplineIncident::findOrFail($id);
        $oldStatus = $incident->statut;

        $incident->update($validated);

        AuditLog::log('discipline_incident_status_changed', DisciplineIncident::class, $id, [
            'old_status' => $oldStatus,
        ], [
            'new_status' => $validated['statut'],
        ]);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'incident' => $incident,
        ]);
    }

    // ==========================================
    // SANCTIONS
    // ==========================================

    /**
     * Liste des sanctions d'un élève
     */
    public function studentSanctions(Request $request, string $studentId)
    {
        $schoolYear = SchoolYear::current();

        $sanctions = DisciplineSanction::where('student_id', $studentId)
            ->where('school_year_id', $schoolYear->id)
            ->with('incident:id,date_incident,type,gravite')
            ->orderByDesc('date_effet')
            ->get();

        // Résumé
        $summary = [
            'avertissements' => $sanctions->whereIn('type', ['avertissement_oral', 'avertissement_ecrit'])->count(),
            'blames' => $sanctions->where('type', 'blame')->count(),
            'retenues' => $sanctions->where('type', 'retenue')->count(),
            'exclusions' => $sanctions->whereIn('type', ['exclusion_temporaire', 'exclusion_cours'])->count(),
            'jours_exclusion' => $sanctions->sum('duree_jours'),
        ];

        return response()->json([
            'sanctions' => $sanctions,
            'summary' => $summary,
        ]);
    }

    /**
     * Créer une sanction
     */
    public function createSanction(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => 'required|uuid|exists:school_college.discipline_incidents,id',
            'type' => 'required|string',
            'motif' => 'required|string|min:10',
            'date_effet' => 'required|date',
            'date_fin' => 'nullable|date|after:date_effet',
            'duree_jours' => 'nullable|integer|min:1|max:30',
            'niveau_decision' => 'required|in:enseignant,censorat,direction,conseil',
            'observations' => 'nullable|string',
            'notifier_parents' => 'boolean',
        ]);

        $incident = DisciplineIncident::findOrFail($validated['incident_id']);

        // Vérifier le niveau de décision
        $typeInfo = DisciplineSanction::TYPES[$validated['type']] ?? null;
        if (!$typeInfo) {
            return response()->json(['message' => 'Type de sanction invalide.'], 422);
        }

        $validated['student_id'] = $incident->student_id;
        $validated['school_year_id'] = $incident->school_year_id;
        $validated['decide_par'] = $request->user()->id;

        DB::beginTransaction();
        try {
            $sanction = DisciplineSanction::create($validated);

            // Mettre à jour le statut de l'incident
            $incident->update(['statut' => 'traite']);

            // Notifier les parents si demandé
            if ($request->get('notifier_parents', false) || $typeInfo['notification_obligatoire']) {
                $sanction->notifyParents('sms');

                // Envoyer SMS
                $this->notificationService->sendSMS(
                    $incident->student->guardian->telephone ?? '',
                    "Sanction disciplinaire pour {$incident->student->full_name}: {$typeInfo['label']}. " .
                        "Motif: {$validated['motif']}. Contactez l'établissement."
                );
            }

            AuditLog::log('discipline_sanction_created', DisciplineSanction::class, $sanction->id, null, [
                'student_id' => $sanction->student_id,
                'type' => $sanction->type,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Sanction enregistrée.',
                'sanction' => $sanction->load('incident'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ==========================================
    // STATISTIQUES
    // ==========================================

    /**
     * Statistiques disciplinaires
     */
    public function stats(Request $request)
    {
        $schoolYear = SchoolYear::current();

        // Par type d'incident
        $byType = DisciplineIncident::where('school_year_id', $schoolYear->id)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        // Par gravité
        $byGravity = DisciplineIncident::where('school_year_id', $schoolYear->id)
            ->select('gravite', DB::raw('COUNT(*) as count'))
            ->groupBy('gravite')
            ->get()
            ->keyBy('gravite');

        // Par classe
        $byClass = DisciplineIncident::where('school_year_id', $schoolYear->id)
            ->select('class_id', DB::raw('COUNT(*) as count'))
            ->groupBy('class_id')
            ->with('class:id,niveau,nom')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Évolution mensuelle
        $monthly = DisciplineIncident::where('school_year_id', $schoolYear->id)
            ->select(
                DB::raw('MONTH(date_incident) as mois'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // Sanctions par type
        $sanctionsByType = DisciplineSanction::where('school_year_id', $schoolYear->id)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        // Total
        $totals = [
            'incidents' => DisciplineIncident::where('school_year_id', $schoolYear->id)->count(),
            'incidents_pending' => DisciplineIncident::where('school_year_id', $schoolYear->id)->pending()->count(),
            'sanctions' => DisciplineSanction::where('school_year_id', $schoolYear->id)->count(),
            'jours_exclusion' => DisciplineSanction::where('school_year_id', $schoolYear->id)->sum('duree_jours'),
        ];

        return response()->json([
            'totals' => $totals,
            'by_type' => $byType,
            'by_gravity' => $byGravity,
            'by_class' => $byClass,
            'by_month' => $monthly,
            'sanctions_by_type' => $sanctionsByType,
        ]);
    }

    /**
     * Élèves avec le plus d'incidents
     */
    public function topIncidents(Request $request)
    {
        $schoolYear = SchoolYear::current();
        $limit = $request->get('limit', 10);

        $students = DisciplineIncident::where('school_year_id', $schoolYear->id)
            ->select('student_id', DB::raw('COUNT(*) as incidents_count'))
            ->groupBy('student_id')
            ->orderByDesc('incidents_count')
            ->limit($limit)
            ->with('student:id,nom,prenoms,matricule')
            ->get();

        return response()->json($students);
    }

    /**
     * Historique disciplinaire complet d'un élève
     */
    public function studentHistory(string $studentId)
    {
        // Incidents
        $incidents = DisciplineIncident::forStudent($studentId)
            ->with(['class:id,niveau,nom', 'sanctions'])
            ->orderByDesc('date_incident')
            ->get();

        // Sanctions
        $sanctions = DisciplineSanction::where('student_id', $studentId)
            ->with('incident:id,date_incident,type')
            ->orderByDesc('date_effet')
            ->get();

        // Résumé par année
        $summaryByYear = DisciplineSanction::where('student_id', $studentId)
            ->select(
                'school_year_id',
                DB::raw('COUNT(*) as total_sanctions'),
                DB::raw('SUM(duree_jours) as jours_exclusion')
            )
            ->groupBy('school_year_id')
            ->get();

        return response()->json([
            'incidents' => $incidents,
            'sanctions' => $sanctions,
            'summary_by_year' => $summaryByYear,
        ]);
    }
}
