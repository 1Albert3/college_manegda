<?php

namespace App\Http\Controllers\Lycee;

use App\Http\Controllers\Controller;
use App\Models\Lycee\SubjectLycee;
use Illuminate\Http\Request;

/**
 * Contrôleur des matières Lycée
 */
class SubjectLyceeController extends Controller
{
    /**
     * Liste des matières
     * Filtres possibles: niveau (2nde, 1ere, Tle), serie (A, C, D)
     */
    public function index(Request $request)
    {
        $query = SubjectLycee::query();

        if ($request->has('niveau')) {
            $query->where('niveau_minimum', '<=', $request->niveau) // Logique simplifiée
                ->where('niveau_maximum', '>=', $request->niveau);
        }

        if ($request->has('serie')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('series')
                    ->orWhereJsonContains('series', $request->serie);
            });
        }

        $subjects = $query->orderBy('nom')->get();

        return response()->json($subjects);
    }

    /**
     * Liste simplifiée pour les dropdowns
     */
    public function list()
    {
        return response()->json(SubjectLycee::where('is_active', true)->orderBy('nom')->get(['id', 'nom', 'code']));
    }
}
