<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function show($studentId, $trimestre)
    {
        return response()->json([
            'studentId' => $studentId,
            'trimestre' => $trimestre,
            'generalAverage' => 14.5,
            'rank' => 5,
            'appreciation' => 'Tableau d\'Honneur',
            'grades' => [
                ['subject' => 'Mathématiques', 'marks' => [12, 14, 15], 'average' => 13.5, 'classAverage' => 13.8, 'teacher' => 'M. SAWADOGO'],
                ['subject' => 'Français', 'marks' => [15, 16], 'average' => 14.0, 'classAverage' => 14.5, 'teacher' => 'Mme KABORE'],
                ['subject' => 'Histoire-Géo', 'marks' => [18, 17], 'average' => 16.0, 'classAverage' => 16.8, 'teacher' => 'M. DIALLO'],
                ['subject' => 'Anglais', 'marks' => [10, 11], 'average' => 12.0, 'classAverage' => 11.5, 'teacher' => 'Mme TRAORE']
            ]
        ]);
    }
}
