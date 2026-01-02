<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur de Gestion des Absences
 * 
 * Saisie, justification et alertes automatiques
 */
class AttendanceController extends Controller
{
    protected NotificationService $notificationService;

    /**
     * Seuils d'alerte
     */
    const ALERT_THRESHOLDS = [
        'sms_first' => 3,     // 3 absences → SMS
        'sms_second' => 5,    // 5 absences → SMS + convocation
        'direction' => 10,    // 10% → Alerte direction
    ];

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des absences
     */
    public function index(Request $request)
    {
        $connection = $this->getConnection($request->get('level', 'college'));
        $schoolYear = SchoolYear::current();

        $query = DB::connection($connection)
            ->table('attendance')
            ->where('school_year_id', $schoolYear->id)
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->leftJoin('classes', 'attendance.class_id', '=', 'classes.id')
            ->select(
                'attendance.*',
                'students.nom',
                'students.prenoms',
                'students.matricule',
                DB::raw("CONCAT(classes.niveau, ' ', classes.nom) as class_name")
            );

        // Filtres
        if ($request->has('class_id')) {
            $query->where('attendance.class_id', $request->class_id);
        }

        if ($request->has('student_id')) {
            $query->where('attendance.student_id', $request->student_id);
        }

        if ($request->has('type')) {
            $query->where('attendance.type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('attendance.statut', $request->statut);
        }

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('attendance.date', [$request->date_debut, $request->date_fin]);
        }

        $absences = $query->orderByDesc('attendance.date')->paginate($request->per_page ?? 20);

        return response()->json($absences);
    }

    /**
     * Enregistrer une/des absence(s)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:mp,college,lycee',
            'absences' => 'required|array|min:1',
            'absences.*.student_id' => 'required|uuid',
            'absences.*.class_id' => 'required|uuid',
            'absences.*.date' => 'required|date|before_or_equal:today',
            'absences.*.type' => 'required|in:absence,retard',
            'absences.*.demi_journee' => 'nullable|in:matin,apres_midi,journee',
            'absences.*.heure_debut' => 'nullable|date_format:H:i',
            'absences.*.heure_fin' => 'nullable|date_format:H:i',
            'absences.*.duree_minutes' => 'nullable|integer', // Pour les retards
            'absences.*.motif' => 'nullable|string|max:500',
        ]);

        $connection = $this->getConnection($validated['level']);
        $schoolYear = SchoolYear::current();
        $created = [];
        $alerts = [];

        DB::beginTransaction();
        try {
            foreach ($validated['absences'] as $absenceData) {
                $absenceData['school_year_id'] = $schoolYear->id;
                $absenceData['saisi_par'] = $request->user()->id;
                $absenceData['statut'] = 'non_justifiee';
                $absenceData['id'] = \Illuminate\Support\Str::uuid();
                $absenceData['created_at'] = now();
                $absenceData['updated_at'] = now();

                // Vérifier si déjà enregistré
                $exists = DB::connection($connection)
                    ->table('attendance')
                    ->where('student_id', $absenceData['student_id'])
                    ->where('date', $absenceData['date'])
                    ->where('type', $absenceData['type'])
                    ->exists();

                if ($exists) continue;

                DB::connection($connection)->table('attendance')->insert($absenceData);
                $created[] = $absenceData['id'];

                // Vérifier les seuils d'alerte
                $absenceCount = $this->getAbsenceCount($connection, $absenceData['student_id'], $schoolYear->id);
                $alert = $this->checkAlertThreshold($connection, $absenceData['student_id'], $absenceCount);
                if ($alert) {
                    $alerts[] = $alert;
                }
            }

            DB::commit();

            return response()->json([
                'message' => count($created) . ' absence(s) enregistrée(s).',
                'created' => $created,
                'alerts' => $alerts,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Justifier une absence
     */
    public function justify(Request $request, string $id)
    {
        $validated = $request->validate([
            'level' => 'required|in:mp,college,lycee',
            'justification' => 'required|string|min:5',
            'justificatif_path' => 'nullable|string', // Chemin du document uploadé
            'type_justificatif' => 'nullable|in:certificat_medical,lettre_parent,autre',
        ]);

        $connection = $this->getConnection($validated['level']);

        $absence = DB::connection($connection)->table('attendance')->where('id', $id)->first();
        if (!$absence) {
            return response()->json(['message' => 'Absence non trouvée.'], 404);
        }

        DB::connection($connection)->table('attendance')
            ->where('id', $id)
            ->update([
                'statut' => 'justifiee',
                'justification' => $validated['justification'],
                'justificatif_path' => $validated['justificatif_path'] ?? null,
                'type_justificatif' => $validated['type_justificatif'] ?? null,
                'justifie_par' => $request->user()->id,
                'date_justification' => now(),
                'updated_at' => now(),
            ]);

        AuditLog::log('absence_justified', 'Attendance', $id, null, [
            'justification' => $validated['justification'],
        ]);

        return response()->json(['message' => 'Absence justifiée.']);
    }

    /**
     * Compter les absences d'un élève
     */
    private function getAbsenceCount(string $connection, string $studentId, string $schoolYearId): int
    {
        return DB::connection($connection)
            ->table('attendance')
            ->where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->where('type', 'absence')
            ->where('statut', 'non_justifiee')
            ->count();
    }

    /**
     * Vérifier les seuils d'alerte et notifier si nécessaire
     */
    private function checkAlertThreshold(string $connection, string $studentId, int $count): ?array
    {
        $shortLevel = str_replace('school_', '', $connection);

        $student = DB::connection($connection)
            ->table("students_{$shortLevel}")
            ->where('id', $studentId)
            ->first();

        if (!$student) return null;

        $guardian = DB::connection($connection)
            ->table("guardians_{$shortLevel}")
            ->where('student_id', $studentId)
            ->first();

        $phone = $guardian->telephone ?? $guardian->telephone_1 ?? null;

        // 3 absences: Premier SMS
        if ($count == self::ALERT_THRESHOLDS['sms_first'] && $phone) {
            $this->notificationService->sendSMS(
                $phone,
                "ALERTE ABSENCE: Votre enfant {$student->prenoms} {$student->nom} cumule {$count} absences non justifiées. " .
                    "Merci de contacter l'établissement. - " . config('app.school_name')
            );

            AuditLog::log('absence_alert_sms', 'Attendance', null, null, [
                'student_id' => $studentId,
                'count' => $count,
                'type' => 'first_alert',
            ]);

            return [
                'student_id' => $studentId,
                'student_name' => "{$student->prenoms} {$student->nom}",
                'type' => 'sms_sent',
                'count' => $count,
            ];
        }

        // 5 absences: Deuxième SMS + convocation
        if ($count == self::ALERT_THRESHOLDS['sms_second'] && $phone) {
            $this->notificationService->sendSMS(
                $phone,
                "URGENT - CONVOCATION: {$student->prenoms} {$student->nom} totalise {$count} absences non justifiées. " .
                    "Vous êtes convoqué(e) à l'établissement dans les plus brefs délais. - " . config('app.school_name')
            );

            AuditLog::log('absence_alert_convocation', 'Attendance', null, null, [
                'student_id' => $studentId,
                'count' => $count,
            ]);

            return [
                'student_id' => $studentId,
                'student_name' => "{$student->prenoms} {$student->nom}",
                'type' => 'convocation_sent',
                'count' => $count,
            ];
        }

        return null;
    }

    /**
     * Statistiques d'absences
     */
    public function stats(Request $request)
    {
        $connection = $this->getConnection($request->get('level', 'college'));
        $schoolYear = SchoolYear::current();

        // Totaux
        $total = DB::connection($connection)
            ->table('attendance')
            ->where('school_year_id', $schoolYear->id)
            ->where('type', 'absence')
            ->count();

        $justified = DB::connection($connection)
            ->table('attendance')
            ->where('school_year_id', $schoolYear->id)
            ->where('type', 'absence')
            ->where('statut', 'justifiee')
            ->count();

        $unjustified = $total - $justified;

        // Retards
        $retards = DB::connection($connection)
            ->table('attendance')
            ->where('school_year_id', $schoolYear->id)
            ->where('type', 'retard')
            ->count();

        // Par classe
        $byClass = DB::connection($connection)
            ->table('attendance as a')
            ->join('classes', 'a.class_id', '=', 'classes.id')
            ->where('a.school_year_id', $schoolYear->id)
            ->where('a.type', 'absence')
            ->select('classes.id', DB::raw("CONCAT(classes.niveau, ' ', classes.nom) as class_name"), DB::raw('COUNT(*) as count'))
            ->groupBy('classes.id', 'classes.niveau', 'classes.nom')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Évolution mensuelle
        $monthly = DB::connection($connection)
            ->table('attendance')
            ->where('school_year_id', $schoolYear->id)
            ->where('type', 'absence')
            ->select(
                DB::raw('MONTH(date) as mois'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN statut = 'justifiee' THEN 1 ELSE 0 END) as justifiees")
            )
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // Élèves avec le plus d'absences
        $topAbsent = DB::connection($connection)
            ->table('attendance as a')
            ->join('students', 'a.student_id', '=', 'students.id')
            ->where('a.school_year_id', $schoolYear->id)
            ->where('a.type', 'absence')
            ->where('a.statut', 'non_justifiee')
            ->select('students.id', 'students.nom', 'students.prenoms', 'students.matricule', DB::raw('COUNT(*) as count'))
            ->groupBy('students.id', 'students.nom', 'students.prenoms', 'students.matricule')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'totals' => [
                'absences' => $total,
                'justified' => $justified,
                'unjustified' => $unjustified,
                'retards' => $retards,
                'taux_justification' => $total > 0 ? round(($justified / $total) * 100, 1) : 0,
            ],
            'by_class' => $byClass,
            'monthly' => $monthly,
            'top_absent' => $topAbsent,
        ]);
    }

    /**
     * Élèves à convoquer (beaucoup d'absences non justifiées)
     */
    public function toConvoke(Request $request)
    {
        $connection = $this->getConnection($request->get('level', 'college'));
        $schoolYear = SchoolYear::current();
        $threshold = $request->get('threshold', 5);

        $students = DB::connection($connection)
            ->table('attendance as a')
            ->join('students', 'a.student_id', '=', 'students.id')
            ->leftJoin('classes', 'a.class_id', '=', 'classes.id')
            ->leftJoin('guardians', 'students.id', '=', 'guardians.student_id')
            ->where('a.school_year_id', $schoolYear->id)
            ->where('a.type', 'absence')
            ->where('a.statut', 'non_justifiee')
            ->select(
                'students.id',
                'students.nom',
                'students.prenoms',
                'students.matricule',
                DB::raw("CONCAT(classes.niveau, ' ', classes.nom) as class_name"),
                'guardians.telephone',
                'guardians.email',
                DB::raw('COUNT(*) as absences_count')
            )
            ->groupBy('students.id', 'students.nom', 'students.prenoms', 'students.matricule', 'classes.niveau', 'classes.nom', 'guardians.telephone', 'guardians.email')
            ->having('absences_count', '>=', $threshold)
            ->orderByDesc('absences_count')
            ->get();

        return response()->json($students);
    }

    /**
     * Envoyer des alertes en masse
     */
    public function sendBulkAlerts(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:mp,college,lycee',
            'student_ids' => 'required|array|min:1',
            'message_type' => 'required|in:reminder,convocation,custom',
            'custom_message' => 'required_if:message_type,custom|string|max:300',
        ]);

        $connection = $this->getConnection($validated['level']);
        $shortLevel = str_replace('school_', '', $connection);
        $sent = 0;
        $failed = [];

        foreach ($validated['student_ids'] as $studentId) {
            $student = DB::connection($connection)
                ->table("students_{$shortLevel}")
                ->where('id', $studentId)
                ->first();

            $guardian = DB::connection($connection)
                ->table("guardians_{$shortLevel}")
                ->where('student_id', $studentId)
                ->first();

            $phone = $guardian->telephone ?? $guardian->telephone_1 ?? null;

            if (!$phone) {
                $failed[] = ['student_id' => $studentId, 'reason' => 'Pas de téléphone'];
                continue;
            }

            // Construire le message
            $message = match ($validated['message_type']) {
                'reminder' => "Rappel: Votre enfant {$student->prenoms} {$student->nom} a des absences non justifiées. " .
                    "Merci de régulariser la situation. - " . config('app.school_name'),
                'convocation' => "CONVOCATION: Vous êtes prié(e) de vous présenter à l'établissement concernant " .
                    "les absences de {$student->prenoms} {$student->nom}. - " . config('app.school_name'),
                'custom' => str_replace(
                    ['{eleve}', '{ecole}'],
                    ["{$student->prenoms} {$student->nom}", config('app.school_name')],
                    $validated['custom_message']
                ),
            };

            $success = $this->notificationService->sendSMS($phone, $message);

            if ($success) {
                $sent++;
            } else {
                $failed[] = ['student_id' => $studentId, 'reason' => 'Échec envoi SMS'];
            }
        }

        AuditLog::log('bulk_absence_alerts_sent', null, null, null, [
            'sent' => $sent,
            'failed' => count($failed),
            'type' => $validated['message_type'],
        ]);

        return response()->json([
            'message' => "{$sent} SMS envoyé(s).",
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    /**
     * Obtenir la connexion selon le niveau
     */
    private function getConnection(string $level): string
    {
        return match ($level) {
            'mp' => 'school_mp',
            'college' => 'school_college',
            'lycee' => 'school_lycee',
            default => 'school_college',
        };
    }
}
