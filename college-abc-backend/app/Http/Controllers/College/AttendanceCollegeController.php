<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Models\College\AttendanceCollege;
use App\Models\College\StudentCollege;
use Illuminate\Http\Request;

class AttendanceCollegeController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\College\AttendanceCollege::with(['student']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid',
            'date' => 'required|date',
            'statut' => 'required|in:present,absent,retard',
            'motif' => 'nullable|string',
        ]);

        $attendance = \App\Models\College\AttendanceCollege::create($validated);

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
            $results[] = \App\Models\College\AttendanceCollege::updateOrCreate(
                ['student_id' => $att['student_id'], 'date' => $request->date],
                ['statut' => $att['statut'], 'motif' => $att['motif'] ?? null]
            );
        }

        return response()->json(['message' => 'Appel enregistré.', 'data' => $results]);
    }

    public function destroy($id)
    {
        \App\Models\College\AttendanceCollege::destroy($id);
        return response()->json(['message' => 'Supprimé.']);
    }
}
