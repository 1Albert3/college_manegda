<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function show($studentId)
    {
        return response()->json([
            'totalHours' => 2,
            'list' => [
                ['date' => '12 Oct 2024', 'timeSlot' => '08h - 10h', 'subject' => 'Mathématiques', 'reason' => 'Rendez-vous médical', 'status' => 'Justifiée']
            ]
        ]);
    }
}
