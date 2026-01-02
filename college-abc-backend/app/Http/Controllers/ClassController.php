<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $classes = Classroom::active()
                ->withCount('students')
                ->orderBy('name')
                ->get();

            return $this->successResponse($classes, 'Classes récupérées avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des classes', 500);
        }
    }

    public function show($id)
    {
        try {
            $class = Classroom::with(['students.parents'])
                ->findOrFail($id);

            return $this->successResponse($class, 'Classe récupérée avec succès');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Classe non trouvée');
        }
    }

    public function students($id)
    {
        try {
            $class = Classroom::findOrFail($id);
            $students = $class->students()
                ->with(['parents', 'currentEnrollment'])
                ->get();

            return $this->successResponse($students, 'Élèves de la classe récupérés');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération', 500);
        }
    }
}