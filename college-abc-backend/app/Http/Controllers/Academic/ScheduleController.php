<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Contrôleur Emploi du Temps
 * 
 * Gestion et génération automatique des emplois du temps
 */
class ScheduleController extends Controller
{
    /**
     * Créneaux horaires standards
     */
    const TIME_SLOTS = [
        ['start' => '07:30', 'end' => '08:30', 'type' => 'cours'],
        ['start' => '08:30', 'end' => '09:30', 'type' => 'cours'],
        ['start' => '09:30', 'end' => '09:45', 'type' => 'recreation'],
        ['start' => '09:45', 'end' => '10:45', 'type' => 'cours'],
        ['start' => '10:45', 'end' => '11:45', 'type' => 'cours'],
        ['start' => '11:45', 'end' => '12:00', 'type' => 'cours'], // Fin matinée
        ['start' => '12:00', 'end' => '15:00', 'type' => 'pause_dejeuner'],
        ['start' => '15:00', 'end' => '16:00', 'type' => 'cours'],
        ['start' => '16:00', 'end' => '17:00', 'type' => 'cours'],
    ];

    /**
     * Jours de la semaine
     */
    const DAYS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    /**
     * Liste des emplois du temps
     */
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::current();
        $level = $request->get('level', 'college');
        $connection = $this->getConnection($level);

        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 404);
        }

        $schedules = DB::connection($connection)
            ->table('schedules')
            ->where('school_year_id', $schoolYear->id);

        if ($request->has('class_id')) {
            $schedules->where('class_id', $request->class_id);
        }

        if ($request->has('teacher_id')) {
            $schedules->where('teacher_id', $request->teacher_id);
        }

        return response()->json(['data' => $schedules->get()]);
    }

    /**
     * Afficher un créneau spécifique
     */
    public function show(Request $request, string $id)
    {
        $level = $request->get('level', 'college');
        $connection = $this->getConnection($level);

        $slot = DB::connection($connection)->table('schedules')->where('id', $id)->first();

        if (!$slot && !$request->has('level')) {
            // Tentative sur les autres niveaux si non trouvé
            foreach (['mp', 'lycee'] as $l) {
                $conn = $this->getConnection($l);
                $slot = DB::connection($conn)->table('schedules')->where('id', $id)->first();
                if ($slot) break;
            }
        }

        if (!$slot) {
            return response()->json(['message' => 'Créneau non trouvé.'], 404);
        }

        return response()->json(['data' => $slot]);
    }

    /**
     * Emploi du temps par classe
     */
    public function byClass(Request $request, string $classId)
    {
        $level = $request->get('level', 'college');
        $connection = $this->getConnection($level);
        $schoolYear = SchoolYear::current();

        $slots = DB::connection($connection)
            ->table('schedules as s')
            ->join('subjects_' . $this->getShortLevel($level) . ' as sub', 's.subject_id', '=', 'sub.id')
            ->leftJoin('teachers_' . $this->getShortLevel($level) . ' as t', 's.teacher_id', '=', 't.id')
            ->where('s.class_id', $classId)
            ->where('s.school_year_id', $schoolYear->id)
            ->select(
                's.*',
                'sub.nom as subject',
                'sub.code as subject_code',
                DB::raw("CONCAT(t.prenom, ' ', t.nom) as teacher"),
                's.room'
            )
            ->orderBy('s.day_number')
            ->orderBy('s.start_time')
            ->get()
            ->map(fn($slot) => [
                'id' => $slot->id,
                'day' => self::DAYS[$slot->day_number - 1] ?? '',
                'day_number' => $slot->day_number,
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => substr($slot->end_time, 0, 5),
                'subject' => $slot->subject,
                'subject_code' => $slot->subject_code,
                'teacher' => $slot->teacher,
                'room' => $slot->room,
            ]);

        return response()->json([
            'class_id' => $classId,
            'schedule' => $slots,
        ]);
    }

    /**
     * Emploi du temps par enseignant
     */
    public function byTeacher(Request $request, string $teacherId)
    {
        $level = $request->get('level', 'college');
        $connection = $this->getConnection($level);
        $schoolYear = SchoolYear::current();

        $slots = DB::connection($connection)
            ->table('schedules as s')
            ->join('subjects_' . $this->getShortLevel($level) . ' as sub', 's.subject_id', '=', 'sub.id')
            ->join('classes_' . $this->getShortLevel($level) . ' as c', 's.class_id', '=', 'c.id')
            ->where('s.teacher_id', $teacherId)
            ->where('s.school_year_id', $schoolYear->id)
            ->select(
                's.*',
                'sub.nom as subject',
                'sub.code as subject_code',
                DB::raw("CONCAT(c.niveau, ' ', c.nom) as class_name"),
                's.room'
            )
            ->orderBy('s.day_number')
            ->orderBy('s.start_time')
            ->get();

        return response()->json([
            'teacher_id' => $teacherId,
            'schedule' => $slots,
        ]);
    }

    /**
     * Générer automatiquement l'emploi du temps
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:mp,college,lycee',
            'class_ids' => 'nullable|array',
            'options' => 'nullable|array',
        ]);

        $connection = $this->getConnection($validated['level']);
        $schoolYear = SchoolYear::current();
        $shortLevel = $this->getShortLevel($validated['level']);

        // Récupérer les classes
        $classQuery = DB::connection($connection)->table("classes_{$shortLevel}")->where('active', true);
        if (!empty($validated['class_ids'])) {
            $classQuery->whereIn('id', $validated['class_ids']);
        }
        $classes = $classQuery->get();

        // Récupérer les affectations enseignants-matières
        $assignments = DB::connection($connection)
            ->table("teacher_subject_assignments as tsa")
            ->join("subjects_{$shortLevel} as s", 'tsa.subject_id', '=', 's.id')
            ->join("teachers_{$shortLevel} as t", 'tsa.teacher_id', '=', 't.id')
            ->where('tsa.school_year_id', $schoolYear->id)
            ->select('tsa.*', 's.nom as subject_name', 's.heures_semaine', 't.nom as teacher_name')
            ->get()
            ->groupBy('class_id');

        $generatedSchedules = [];
        $conflicts = [];

        foreach ($classes as $class) {
            $classAssignments = $assignments->get($class->id) ?? collect();

            $result = $this->generateClassSchedule(
                $connection,
                $class,
                $classAssignments,
                $schoolYear->id,
                $validated['options'] ?? []
            );

            $generatedSchedules[$class->id] = $result['slots'];
            if (!empty($result['conflicts'])) {
                $conflicts[$class->id] = $result['conflicts'];
            }
        }

        AuditLog::log('schedule_generated', null, null, null, [
            'level' => $validated['level'],
            'classes_count' => $classes->count(),
            'conflicts_count' => count($conflicts),
        ]);

        return response()->json([
            'message' => 'Emplois du temps générés.',
            'generated' => count($generatedSchedules),
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Générer l'emploi du temps pour une classe
     */
    private function generateClassSchedule($connection, $class, $assignments, $schoolYearId, $options = []): array
    {
        $slots = [];
        $conflicts = [];
        $usedSlots = []; // [day][time] => teacher_id

        // Supprimer l'ancien emploi du temps
        DB::connection($connection)
            ->table('schedules')
            ->where('class_id', $class->id)
            ->where('school_year_id', $schoolYearId)
            ->delete();

        // Pour chaque matière affectée
        foreach ($assignments as $assignment) {
            $hoursNeeded = $assignment->heures_semaine ?? 2;
            $hoursAssigned = 0;

            // Chercher des créneaux libres
            foreach (self::DAYS as $dayIndex => $dayName) {
                if ($hoursAssigned >= $hoursNeeded) break;

                foreach (self::TIME_SLOTS as $timeSlot) {
                    if ($timeSlot['type'] !== 'cours') continue;
                    if ($hoursAssigned >= $hoursNeeded) break;

                    $slotKey = ($dayIndex + 1) . '_' . $timeSlot['start'];

                    // Vérifier si le créneau est libre pour la classe
                    if (isset($usedSlots[$slotKey])) continue;

                    // Vérifier si l'enseignant est disponible
                    $teacherBusy = $this->isTeacherBusy(
                        $connection,
                        $assignment->teacher_id,
                        $dayIndex + 1,
                        $timeSlot['start'],
                        $schoolYearId,
                        $class->id
                    );

                    if ($teacherBusy) {
                        $conflicts[] = [
                            'type' => 'teacher_conflict',
                            'day' => $dayName,
                            'time' => $timeSlot['start'],
                            'teacher' => $assignment->teacher_name,
                            'subject' => $assignment->subject_name,
                        ];
                        continue;
                    }

                    // Créer le créneau
                    $slotId = \Illuminate\Support\Str::uuid();
                    DB::connection($connection)->table('schedules')->insert([
                        'id' => $slotId,
                        'class_id' => $class->id,
                        'subject_id' => $assignment->subject_id,
                        'teacher_id' => $assignment->teacher_id,
                        'school_year_id' => $schoolYearId,
                        'day_number' => $dayIndex + 1,
                        'day_name' => $dayName,
                        'start_time' => $timeSlot['start'],
                        'end_time' => $timeSlot['end'],
                        'room' => $class->salle ?? 'Salle ' . $class->nom,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $usedSlots[$slotKey] = $assignment->teacher_id;
                    $slots[] = $slotId;
                    $hoursAssigned++;
                }
            }

            // Vérifier si toutes les heures ont été assignées
            if ($hoursAssigned < $hoursNeeded) {
                $conflicts[] = [
                    'type' => 'insufficient_slots',
                    'subject' => $assignment->subject_name,
                    'needed' => $hoursNeeded,
                    'assigned' => $hoursAssigned,
                ];
            }
        }

        return [
            'slots' => $slots,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Vérifier si un enseignant est occupé
     */
    private function isTeacherBusy($connection, $teacherId, $dayNumber, $startTime, $schoolYearId, $excludeClassId = null): bool
    {
        $query = DB::connection($connection)
            ->table('schedules')
            ->where('teacher_id', $teacherId)
            ->where('day_number', $dayNumber)
            ->where('start_time', $startTime)
            ->where('school_year_id', $schoolYearId);

        if ($excludeClassId) {
            $query->where('class_id', '!=', $excludeClassId);
        }

        return $query->exists();
    }

    /**
     * Modifier un créneau
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'level' => 'required|in:mp,college,lycee',
            'day_number' => 'nullable|integer|min:1|max:6',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'room' => 'nullable|string|max:50',
            'teacher_id' => 'nullable|uuid',
        ]);

        $connection = $this->getConnection($validated['level']);

        $slot = DB::connection($connection)->table('schedules')->where('id', $id)->first();
        if (!$slot) {
            return response()->json(['message' => 'Créneau non trouvé.'], 404);
        }

        // Vérifier les conflits si changement d'horaire ou d'enseignant
        if (isset($validated['teacher_id']) || isset($validated['day_number']) || isset($validated['start_time'])) {
            $teacherId = $validated['teacher_id'] ?? $slot->teacher_id;
            $dayNumber = $validated['day_number'] ?? $slot->day_number;
            $startTime = $validated['start_time'] ?? $slot->start_time;

            if ($this->isTeacherBusy($connection, $teacherId, $dayNumber, $startTime, $slot->school_year_id, $slot->class_id)) {
                return response()->json([
                    'message' => 'Conflit: l\'enseignant est déjà occupé sur ce créneau.',
                ], 422);
            }
        }

        // Mise à jour
        $updateData = array_filter([
            'day_number' => $validated['day_number'] ?? null,
            'day_name' => isset($validated['day_number']) ? self::DAYS[$validated['day_number'] - 1] : null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'room' => $validated['room'] ?? null,
            'teacher_id' => $validated['teacher_id'] ?? null,
            'updated_at' => now(),
        ], fn($v) => $v !== null);

        DB::connection($connection)->table('schedules')->where('id', $id)->update($updateData);

        AuditLog::log('schedule_slot_updated', 'Schedule', $id, ['slot' => (array)$slot], $updateData);

        return response()->json(['message' => 'Créneau mis à jour.']);
    }

    /**
     * Supprimer un créneau
     */
    public function destroy(Request $request, string $id)
    {
        $connection = $this->getConnection($request->get('level', 'college'));

        $deleted = DB::connection($connection)->table('schedules')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Créneau non trouvé.'], 404);
        }

        return response()->json(['message' => 'Créneau supprimé.']);
    }

    /**
     * Export PDF
     */
    public function exportPdf(Request $request)
    {
        $level = $request->get('level', 'college');
        $connection = $this->getConnection($level);
        $schoolYear = SchoolYear::current();

        $classId = $request->get('class_id');
        $teacherId = $request->get('teacher_id');

        if ($classId) {
            $data = $this->getPdfDataForClass($connection, $classId, $schoolYear->id, $level);
            $view = 'pdf.schedule_class';
            $filename = 'emploi_du_temps_classe.pdf';
        } elseif ($teacherId) {
            $data = $this->getPdfDataForTeacher($connection, $teacherId, $schoolYear->id, $level);
            $view = 'pdf.schedule_teacher';
            $filename = 'emploi_du_temps_enseignant.pdf';
        } else {
            return response()->json(['message' => 'Spécifiez class_id ou teacher_id.'], 422);
        }

        $pdf = Pdf::loadView($view, [
            'schedule' => $data['schedule'],
            'entity' => $data['entity'],
            'days' => self::DAYS,
            'time_slots' => array_filter(self::TIME_SLOTS, fn($s) => $s['type'] === 'cours'),
            'school_year' => $schoolYear->name,
            'school_name' => config('app.school_name'),
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Données PDF pour une classe
     */
    private function getPdfDataForClass($connection, $classId, $schoolYearId, $level): array
    {
        $shortLevel = $this->getShortLevel($level);

        $class = DB::connection($connection)
            ->table("classes_{$shortLevel}")
            ->where('id', $classId)
            ->first();

        $slots = DB::connection($connection)
            ->table('schedules as s')
            ->join("subjects_{$shortLevel} as sub", 's.subject_id', '=', 'sub.id')
            ->leftJoin("teachers_{$shortLevel} as t", 's.teacher_id', '=', 't.id')
            ->where('s.class_id', $classId)
            ->where('s.school_year_id', $schoolYearId)
            ->select('s.*', 'sub.nom as subject', 'sub.code', DB::raw("CONCAT(t.prenom, ' ', t.nom) as teacher"))
            ->get();

        // Organiser par jour/heure
        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot->day_number][$slot->start_time] = $slot;
        }

        return [
            'entity' => $class,
            'schedule' => $grid,
        ];
    }

    /**
     * Données PDF pour un enseignant
     */
    private function getPdfDataForTeacher($connection, $teacherId, $schoolYearId, $level): array
    {
        $shortLevel = $this->getShortLevel($level);

        $teacher = DB::connection($connection)
            ->table("teachers_{$shortLevel}")
            ->where('id', $teacherId)
            ->first();

        $slots = DB::connection($connection)
            ->table('schedules as s')
            ->join("subjects_{$shortLevel} as sub", 's.subject_id', '=', 'sub.id')
            ->join("classes_{$shortLevel} as c", 's.class_id', '=', 'c.id')
            ->where('s.teacher_id', $teacherId)
            ->where('s.school_year_id', $schoolYearId)
            ->select('s.*', 'sub.nom as subject', DB::raw("CONCAT(c.niveau, ' ', c.nom) as class_name"))
            ->get();

        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot->day_number][$slot->start_time] = $slot;
        }

        return [
            'entity' => $teacher,
            'schedule' => $grid,
        ];
    }

    /**
     * Obtenir la connexion DB selon le niveau
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

    /**
     * Obtenir le suffixe court
     */
    private function getShortLevel(string $level): string
    {
        return match ($level) {
            'mp' => 'mp',
            'college' => 'college',
            'lycee' => 'lycee',
            default => 'college',
        };
    }

    /**
     * Créneaux disponibles
     */
    public function getTimeSlots()
    {
        return response()->json([
            'days' => self::DAYS,
            'slots' => self::TIME_SLOTS,
        ]);
    }
}
