<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index($studentId)
    {
        return response()->json([
            ['id' => 1, 'title' => 'Bulletin Trimestre 1', 'type' => 'PDF', 'date' => '15 Jan 2025', 'iconColor' => 'red'],
            ['id' => 2, 'title' => 'Certificat de Scolarité', 'type' => 'PDF', 'date' => 'Année 2024-2025', 'iconColor' => 'blue'],
            ['id' => 3, 'title' => 'Communiqué Rentrée', 'type' => 'PDF', 'date' => 'Information Générale', 'iconColor' => 'green']
        ]);
    }
}
