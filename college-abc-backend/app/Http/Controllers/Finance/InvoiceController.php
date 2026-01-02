<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MP\StudentMP;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;

/**
 * Contrôleur des Factures
 * 
 * Gestion du cycle de vie des factures élève
 */
class InvoiceController extends Controller
{
    /**
     * Liste des factures avec recherche et filtres
     */
    public function index(Request $request)
    {
        $query = Invoice::query();

        // Recherche par numéro ou élève (TODO: Joindre selon student_database)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtres par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtres par type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $invoices = $query->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        // Load student names manually
        $invoices->getCollection()->transform(function ($invoice) {
            $student = null;
            if ($invoice->student_database === 'school_mp' || $invoice->student_database === 'mp') {
                $student = StudentMP::find($invoice->student_id);
            } elseif ($invoice->student_database === 'school_college' || $invoice->student_database === 'college') {
                $student = StudentCollege::find($invoice->student_id);
            } elseif ($invoice->student_database === 'school_lycee' || $invoice->student_database === 'lycee') {
                $student = StudentLycee::find($invoice->student_id);
            }

            if ($student) {
                $invoice->student_name = $student->nom . ' ' . $student->prenoms;
                $invoice->student_matricule = $student->matricule;
                // Add student object for frontend convenience
                $invoice->student = [
                    'first_name' => $student->prenoms,
                    'last_name' => $student->nom
                ];
            } else {
                $invoice->student_name = "Élève Inconnu";
                $invoice->student_matricule = "?";
                $invoice->student = ['first_name' => 'Inconnu', 'last_name' => ''];
            }
            $invoice->is_overdue = $invoice->isOverdue();
            return $invoice;
        });

        return response()->json($invoices);
    }

    /**
     * Créer une nouvelle facture
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid',
            'student_database' => 'required|string',
            'school_year_id' => 'required|uuid',
            'type' => 'required|in:inscription,scolarite,cantine,transport,fournitures,autre',
            'montant_ttc' => 'required|numeric|min:0',
            'date_emission' => 'required|date',
            'date_echeance' => 'nullable|date|after_or_equal:date_emission',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Mapping des noms pour la base de données
        $dbMap = [
            'mp' => 'school_mp',
            'maternelle' => 'school_mp',
            'primaire' => 'school_mp',
            'college' => 'school_college',
            'lycee' => 'school_lycee',
            'lycée' => 'school_lycee',
        ];

        $db = strtolower($validated['student_database']);
        $validated['student_database'] = $dbMap[$db] ?? $db;

        $validated['created_by'] = $request->user()->id;
        $validated['statut'] = 'emise';

        DB::beginTransaction();
        try {
            $invoice = Invoice::create($validated);

            AuditLog::log('invoice_created', Invoice::class, $invoice->id, null, [
                'number' => $invoice->number,
                'amount' => $invoice->montant_ttc
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Facture créée avec succès.',
                'invoice' => $invoice
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Détails d'une facture
     */
    public function show(string $id)
    {
        $invoice = Invoice::with(['payments.receiver'])->findOrFail($id);

        // Simuler les infos élèves (à realiser avec un service dédié multi-db)
        $invoice->student = [
            'name' => 'Jean Durand',
            'matricule' => 'STU001',
            'class' => '6ème A'
        ];

        return response()->json($invoice);
    }

    /**
     * Annuler une facture
     */
    public function cancel(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->montant_paye > 0) {
            return response()->json([
                'message' => 'Impossible d\'annuler une facture ayant déjà des paiements.'
            ], 422);
        }

        $invoice->update(['statut' => 'annulee']);

        AuditLog::log('invoice_cancelled', Invoice::class, $invoice->id);

        return response()->json([
            'message' => 'Facture annulée avec succès.',
            'invoice' => $invoice
        ]);
    }

    /**
     * Générer le PDF de la facture
     */
    public function print(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        // TODO: Utiliser une vue Blade réelle
        $pdf = Pdf::loadHTML("<h1>Facture {$invoice->number}</h1><p>Montant: {$invoice->montant_ttc} FCFA</p>");

        return $pdf->download("facture_{$invoice->number}.pdf");
    }
}
