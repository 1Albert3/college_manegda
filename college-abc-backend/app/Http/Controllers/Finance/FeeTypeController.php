<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FeeStructure;
use Illuminate\Http\Request;

/**
 * Contrôleur des Configurations Tarifaires
 */
class FeeTypeController extends Controller
{
    /**
     * Liste des structures tarifaires
     */
    public function index()
    {
        try {
            $feeStructures = FeeStructure::where('is_active', true)
                ->orderBy('cycle')
                ->orderBy('niveau')
                ->get();

            return response()->json($feeStructures);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des configurations'
            ], 500);
        }
    }

    /**
     * Store a new structure (TODO: Implement full CRUD)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|uuid',
            'cycle' => 'required|in:maternelle,primaire,college,lycee',
            'niveau' => 'required|string',
            'inscription' => 'required|numeric',
            'scolarite' => 'required|numeric'
        ]);

        $fee = FeeStructure::create($validated);
        return response()->json($fee, 201);
    }
}
