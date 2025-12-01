<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function show($studentId)
    {
        return response()->json([
            ['day' => 'Lundi', 'startTime' => '07h', 'endTime' => '09h', 'subject' => 'Maths', 'room' => 'Salle 12', 'color' => 'blue'],
            ['day' => 'Mardi', 'startTime' => '07h', 'endTime' => '09h', 'subject' => 'SVT', 'room' => 'Labo 2', 'color' => 'green'],
            ['day' => 'Mercredi', 'startTime' => '07h', 'endTime' => '09h', 'subject' => 'Français', 'room' => 'Salle 12', 'color' => 'purple'],
            ['day' => 'Jeudi', 'startTime' => '07h', 'endTime' => '09h', 'subject' => 'Maths', 'room' => 'Salle 12', 'color' => 'blue'],
            ['day' => 'Vendredi', 'startTime' => '07h', 'endTime' => '09h', 'subject' => 'EPS', 'room' => 'Terrain', 'color' => 'yellow'],
            
            ['day' => 'Lundi', 'startTime' => '10h', 'endTime' => '12h', 'subject' => 'Français', 'room' => 'Salle 12', 'color' => 'purple'],
            ['day' => 'Mardi', 'startTime' => '10h', 'endTime' => '12h', 'subject' => 'Hist-Géo', 'room' => 'Salle 12', 'color' => 'orange'],
            ['day' => 'Jeudi', 'startTime' => '10h', 'endTime' => '12h', 'subject' => 'Anglais', 'room' => 'Salle 12', 'color' => 'red'],
            ['day' => 'Vendredi', 'startTime' => '10h', 'endTime' => '12h', 'subject' => 'Physique', 'room' => 'Labo 1', 'color' => 'teal'],
        ]);
    }
}
