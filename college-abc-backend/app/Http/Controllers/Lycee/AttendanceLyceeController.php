<?php

namespace App\Http\Controllers\Lycee;

use App\Http\Controllers\Controller;
use App\Models\Lycee\AttendanceLycee;
use Illuminate\Http\Request;

class AttendanceLyceeController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLycee::with(['student']);
        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid',
            'date' => 'required|date',
            'statut' => 'required|in:present,absent,retard',
        ]);

        $attendance = AttendanceLycee::create($validated);
        return response()->json($attendance, 201);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|uuid',
            'attendances.*.statut' => 'required|in:present,absent,retard',
        ]);

        $results = [];
        foreach ($request->attendances as $att) {
            $results[] = AttendanceLycee::updateOrCreate(
                ['student_id' => $att['student_id'], 'date' => $request->date],
                ['statut' => $att['statut'], 'motif' => $att['motif'] ?? null]
            );
        }

        return response()->json(['message' => 'Appel Lycée enregistré.', 'data' => $results]);
    }

    public function destroy($id)
    {
        AttendanceLycee::destroy($id);
        return response()->json(['message' => 'Supprimé.']);
    }
}
