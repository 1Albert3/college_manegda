<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Api\PaymentController;
use Modules\Finance\Http\Controllers\Api\InvoiceController;
use Modules\Finance\Http\Controllers\Api\FeeTypeController;

/*
 *--------------------------------------------------------------------------
 * Finance API Routes
 *--------------------------------------------------------------------------
 *
 * Routes API pour le module Finance (Paiements, Factures, Types de frais)
 * Toutes les routes sont protégées par l'authentification Sanctum
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.finance.')->group(function () {
    
    // ============================================
    // PAYMENTS (Paiements)
    // ============================================
    Route::prefix('payments')->name('payments.')->group(function () {
        // CRUD de base
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
        
        // Actions spéciales
        Route::post('/{id}/validate', [PaymentController::class, 'validatePayment'])->name('validate');
        Route::post('/{id}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
        Route::get('/{id}/receipt', [PaymentController::class, 'downloadReceipt'])->name('receipt');
        
        // Statistiques et rapports
        Route::get('/statistics/summary', [PaymentController::class, 'statistics'])->name('statistics');
    });

    // Routes spécifiques élèves (paiements)
    Route::get('/students/{studentId}/payments', [PaymentController::class, 'getStudentPayments'])->name('students.payments');
    Route::get('/students/{studentId}/balance', [PaymentController::class, 'getStudentBalance'])->name('students.balance');

    // ============================================
    // INVOICES (Factures)
    // ============================================
    Route::prefix('invoices')->name('invoices.')->group(function () {
        // CRUD de base
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{id}', [InvoiceController::class, 'show'])->name('show');
        
        // Actions spéciales
        Route::post('/{id}/issue', [InvoiceController::class, 'issue'])->name('issue');
        Route::post('/{id}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
        Route::get('/{id}/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
        
        // Listes et filtres
        Route::get('/unpaid/list', [InvoiceController::class, 'getUnpaid'])->name('unpaid');
        
        // Calculs et simulations
        Route::post('/calculate-due', [InvoiceController::class, 'calculateDue'])->name('calculate-due');
        
        // Export et rapports
        Route::get('/class/{classId}/export', [InvoiceController::class, 'exportByClass'])->name('export-class');
        Route::get('/statistics/summary', [InvoiceController::class, 'statistics'])->name('statistics');
    });

    // ============================================
    // FEE TYPES (Types de frais)
    // ============================================
    Route::prefix('fee-types')->name('fee-types.')->group(function () {
        // CRUD complet
        Route::get('/', [FeeTypeController::class, 'index'])->name('index');
        Route::post('/', [FeeTypeController::class, 'store'])->name('store');
        Route::get('/{id}', [FeeTypeController::class, 'show'])->name('show');
        Route::put('/{id}', [FeeTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [FeeTypeController::class, 'destroy'])->name('destroy');
        
        // Actions spéciales
        Route::post('/{id}/activate', [FeeTypeController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [FeeTypeController::class, 'deactivate'])->name('deactivate');
        
        // Filtres et recherche
        Route::get('/student/{studentId}/applicable', [FeeTypeController::class, 'getApplicableToStudent'])->name('applicable');
    });
});
