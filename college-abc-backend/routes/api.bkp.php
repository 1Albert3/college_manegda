<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MP\ClassMPController;
use App\Http\Controllers\Lycee\ClassLyceeController;
use App\Http\Controllers\Lycee\GradeLyceeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Architecture Zéro Erreur - Routage par Cycle
|
*/

// V1 API
Route::prefix('v1')->group(function () {

    // --- AUTHENTIFICATION ---
    // (À reconnecter avec le vrai AuthController une fois validé)
    Route::post('auth/login', [\App\Http\Controllers\Auth\SimpleAuthController::class, 'login']);

    // --- MATERNELLE & PRIMAIRE (MP) ---
    Route::prefix('mp')->group(function () {
        // Classes (Gestion stricte 15/40 élèves)
        Route::apiResource('classes', ClassMPController::class);

        // Autres routes MP à migrer ici...
    });

    // --- LYCÉE ---
    Route::prefix('lycee')->group(function () {
        // Classes (Gestion Séries A/C/D...)
        Route::apiResource('classes', ClassLyceeController::class);

        // Notes (Saisie groupée & Consultation)
        Route::post('grades/bulk', [GradeLyceeController::class, 'storeBulk']);
        Route::get('students/{student}/grades', [GradeLyceeController::class, 'indexStudent']);
    });

    // --- COLLÈGE (À venir) ---
    Route::prefix('college')->group(function () {
        // Future implémentation
    });
});

// --- ROUTES DE COMPATIBILITÉ (TEMPORAIRE) ---
// Redirections pour éviter de casser le frontend actuel s'il utilise des routes hardcodées
// À supprimer une fois le frontend mis à jour
Route::get('dashboard/direction', function () {
    // Renvoie un objet vide pour l'instant pour ne pas crasher, 
    // mais le vrai dashboard devra appeler les stats des contrôleurs.
    return response()->json(['overview' => [], 'stats' => []]);
});
