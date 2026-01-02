<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ScheduleController - Gestion des emplois du temps
 */
class ScheduleController extends Controller
{
    /**
     * Liste des créneaux (filtres par classe, enseignant ou jour)
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['classRoom', 'subject', 'teacher']);

        if ($request->has('class_room_id')) {
            $query->where('class_room_id', $request->class_room_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Créer un nouveau créneau
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:school_years,id',
            'day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required',
            'end_time' => 'required',
            'room' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Vérification de chevauchement (Overlap detection)
        $overlap = Schedule::where('class_room_id', $validated['class_room_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Un cours existe déjà sur ce créneau pour cette classe.'
            ], 422);
        }

        $schedule = Schedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Créneau ajouté avec succès',
            'data' => $schedule
        ], 201);
    }

    /**
     * Afficher l'emploi du temps complet d'une classe
     */
    public function show($classId)
    {
        $schedules = Schedule::with(['subject', 'teacher'])
            ->where('class_room_id', $classId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Supprimer un créneau
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Créneau supprimé'
        ]);
    }
}
