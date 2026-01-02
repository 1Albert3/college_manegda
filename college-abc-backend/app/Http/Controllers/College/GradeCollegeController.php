<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Models\College\GradeCollege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradeCollegeController extends Controller
{
    /**
     * Liste des matières pour le collège
     */
    public function subjects()
    {
        return response()->json(
            \App\Models\College\SubjectCollege::where('is_active', true)
                ->orderBy('nom')
                ->get()
        );
    }

    /**
     * Saisie groupée (Bulk)
     */
    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|uuid|exists:school_college.classes_college,id',
            'subject_id' => 'required|uuid|exists:school_college.subjects_college,id',
            'school_year_id' => 'required|uuid',
            'trimestre' => 'required|in:1,2,3',
            'type_evaluation' => 'required|in:IE,DS,Comp,TP,CC',
            'date_evaluation' => 'required|date',
            'coefficient' => ['nullable', 'integer', 'min:1'],
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|uuid',
            'grades.*.note' => 'required|numeric|min:0|max:20',
            'grades.*.appreciation' => 'nullable|string',
        ]);

        $createdCount = 0;
        $coeff = $validated['coefficient'] ?? 1;

        foreach ($validated['grades'] as $item) {
            GradeCollege::updateOrCreate(
                [
                    'school_year_id' => $validated['school_year_id'],
                    'class_id' => $validated['class_id'],
                    'subject_id' => $validated['subject_id'],
                    'student_id' => $item['student_id'],
                    'trimestre' => $validated['trimestre'],
                    'type_evaluation' => $validated['type_evaluation'],
                    'date_evaluation' => $validated['date_evaluation'],
                ],
                [
                    'note_obtenue' => $item['note'],
                    'note_sur_20' => $item['note'],
                    'coefficient' => $coeff,
                    'commentaire' => $item['appreciation'] ?? null,
                    'recorded_by' => Auth::id(),
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
            $createdCount++;
        }

        return response()->json(['message' => "$createdCount notes enregistrées (Collège)."]);
    }

    /**
     * Notes d'un étudiant
     */
    public function indexStudent($studentId)
    {
        return response()->json(
            GradeCollege::with('subject')
                ->where('student_id', $studentId)
                ->where('is_published', true)
                ->orderBy('date_evaluation', 'desc')
                ->get()
                ->groupBy('trimestre')
        );
    }
}
