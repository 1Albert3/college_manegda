<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MP\AttendanceMP;
use App\Models\MP\ClassMP;
use App\Models\MP\StudentMP;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur des Absences Maternelle/Primaire
 */
class AttendanceMPController extends Controller
{
    /**
     * Liste des absences avec filtres
     */
    public function index(Request $request)
    {
        $query = AttendanceMP::with(['student', 'class']);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $attendances = $query->orderByDesc('date')->paginate($request->per_page ?? 50);

        return response()->json($attendances);
    }

    /**
     * Enregistrement en masse (ex: appel du matin)
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid|exists:school_mp.classes_mp,id',
            'date' => 'required|date',
            'type' => 'required|in:absence,retard',
            'absents' => 'required|array', // IDs des élèves absents/en retard
            'absents.*.student_id' => 'required|uuid|exists:school_mp.students_mp,id',
            'absents.*.motif' => 'nullable|string|max:255',
            'absents.*.heure_arrivee' => 'nullable|string',
        ]);

        $schoolYear = SchoolYear::current();
        if (!$schoolYear) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $created = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['absents'] as $absData) {
                AttendanceMP::create([
                    'student_id' => $absData['student_id'],
                    'class_id' => $validated['class_id'],
                    'school_year_id' => $schoolYear->id,
                    'date' => $validated['date'],
                    'type' => $validated['type'],
                    'statut' => 'en_attente',
                    'motif' => $absData['motif'] ?? null,
                    'heure_arrivee' => $absData['heure_arrivee'] ?? null,
                    'recorded_by' => $request->user()->id,
                ]);
                $created++;
            }

            DB::commit();

            AuditLog::log('attendance_bulk_created', AttendanceMP::class, null, null, [
                'class_id' => $validated['class_id'],
                'count' => $created,
                'date' => $validated['date']
            ]);

            return response()->json([
                'message' => "{$created} enregistrement(s) d'absence/retard effectués.",
                'count' => $created
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Justifier une absence
     */
    public function justify(Request $request, string $id)
    {
        $attendance = AttendanceMP::findOrFail($id);

        $validated = $request->validate([
            'statut' => 'required|in:justifiee,non_justifiee',
            'motif' => 'nullable|string',
            // 'justificatif' => 'nullable|file|mimes:pdf,jpg,png|max:2048' // A implémenter si besoin
        ]);

        $oldStatut = $attendance->statut;
        $attendance->update([
            'statut' => $validated['statut'],
            'motif' => $validated['motif'] ?? $attendance->motif,
        ]);

        AuditLog::log('attendance_justified', AttendanceMP::class, $attendance->id, ['statut' => $oldStatut], $validated);

        return response()->json([
            'message' => 'Absence mise à jour avec succès.',
            'attendance' => $attendance
        ]);
    }

    /**
     * Supprimer un enregistrement
     */
    public function destroy(string $id)
    {
        $attendance = AttendanceMP::findOrFail($id);
        $attendance->delete();

        AuditLog::log('attendance_deleted', AttendanceMP::class, $id);

        return response()->json(['message' => 'Enregistrement supprimé.']);
    }
}
