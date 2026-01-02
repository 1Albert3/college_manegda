<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    public function __construct()
    {
        // Pas de middleware d'authentification
    }
    public function students(Request $request)
    {
        try {
            $students = \Illuminate\Support\Facades\DB::table('students')
                ->select('id', 'matricule', 'first_name', 'last_name', 'date_of_birth', 'gender')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students,
                'total' => $students->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function classes(Request $request)
    {
        try {
            $classes = \Illuminate\Support\Facades\DB::table('classrooms')
                ->select('id', 'name', 'level', 'capacity', 'is_active')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function dashboard(Request $request)
    {
        try {
            $controller = new \App\Http\Controllers\Dashboard\DirectionDashboardController();
            return $controller->index($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}