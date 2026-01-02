<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Models\College\SubjectCollege;
use Illuminate\Http\Request;

/**
 * Contrôleur des matières Collège
 */
class SubjectCollegeController extends Controller
{
    /**
     * Liste des matières
     * Filtre optionnel: niveau (6eme, 5eme, 4eme, 3eme)
     */
    public function index(Request $request)
    {
        $query = SubjectCollege::active();

        if ($request->has('niveau')) {
            $niveau = $request->niveau;
            // Adaptation du format si reçu '6ème' au lieu de '6eme'
            $niveau = str_replace('ème', 'eme', $niveau);
            $query->forLevel($niveau);
        }

        $subjects = $query->orderBy('nom')->get();

        return response()->json($subjects);
    }
}
