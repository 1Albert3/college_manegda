<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use App\Models\Finance\Payment;
use App\Models\AuditLog;
use App\Models\SchoolYear;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MP\StudentMP;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;

/**
 * Contrôleur des Paiements
 * 
 * Gestion complète des paiements:
 * - Enregistrement des paiements
 * - Génération des reçus
 * - Rapports financiers
 * - Suivi des impayés
 */
class PaymentController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des paiements avec filtres
     */
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'receiver:id,first_name,last_name']);

        // Filtres
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('mode_paiement')) {
            $query->where('mode_paiement', $request->mode_paiement);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->betweenDates($request->date_from, $request->date_to);
        }

        // Tri
        $query->orderByDesc('date_paiement');

        $payments = $query->paginate($request->per_page ?? 20);

        return response()->json($payments);
    }

    /**
     * Enregistrer un nouveau paiement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|uuid|exists:school_core.invoices,id',
            'montant' => 'required|numeric|min:1',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money,carte',
            'date_paiement' => 'required|date|before_or_equal:today',
            'reference_transaction' => 'nullable|string|max:100',
            'banque' => 'nullable|string|max:100',
            'numero_cheque' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        // Vérifier que le montant n'excède pas le solde
        if ($validated['montant'] > $invoice->solde) {
            return response()->json([
                'message' => "Le montant ({$validated['montant']} FCFA) dépasse le solde restant ({$invoice->solde} FCFA).",
            ], 422);
        }

        $validated['student_id'] = $invoice->student_id;
        $validated['student_database'] = $invoice->student_database;
        $validated['received_by'] = $request->user()->id;

        // Auto-valider pour certains modes
        if (in_array($validated['mode_paiement'], ['especes', 'mobile_money'])) {
            $validated['statut'] = 'valide';
            $validated['validated_by'] = $request->user()->id;
            $validated['validated_at'] = now();
        } else {
            $validated['statut'] = 'en_attente';
        }

        DB::beginTransaction();
        try {
            $payment = Payment::create($validated);

            AuditLog::log('payment_created', Payment::class, $payment->id, null, [
                'invoice_id' => $invoice->id,
                'montant' => $payment->montant,
                'mode' => $payment->mode_paiement,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Paiement enregistré avec succès.',
                'payment' => $payment->load('invoice'),
                'invoice_status' => $invoice->fresh()->only(['id', 'solde', 'statut']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Afficher un paiement
     */
    public function show(string $id)
    {
        $payment = Payment::with(['invoice', 'receiver', 'validator'])->findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Valider un paiement (chèque/virement)
     */
    public function validate(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Ce paiement ne peut pas être validé (statut: ' . $payment->statut . ').',
            ], 422);
        }

        $payment->validate($request->user()->id);

        AuditLog::log('payment_validated', Payment::class, $payment->id);

        return response()->json([
            'message' => 'Paiement validé avec succès.',
            'payment' => $payment->fresh(['invoice']),
        ]);
    }

    /**
     * Rejeter un paiement
     */
    public function reject(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);

        if ($payment->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Ce paiement ne peut pas être rejeté.',
            ], 422);
        }

        $payment->reject($request->user()->id, $validated['reason']);

        AuditLog::log('payment_rejected', Payment::class, $payment->id, null, [
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Paiement rejeté.',
            'payment' => $payment->fresh(),
        ]);
    }

    /**
     * Annuler un paiement
     */
    public function cancel(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);

        if ($payment->statut === 'annule') {
            return response()->json([
                'message' => 'Ce paiement est déjà annulé.',
            ], 422);
        }

        $oldStatus = $payment->statut;

        $payment->update([
            'statut' => 'annule',
            'notes' => "{$payment->notes}\nAnnulation: {$validated['reason']}",
        ]);

        AuditLog::log('payment_cancelled', Payment::class, $payment->id, [
            'old_status' => $oldStatus,
        ], [
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Paiement annulé.',
            'payment' => $payment->fresh(['invoice']),
        ]);
    }

    /**
     * Générer le reçu de paiement (PDF)
     */
    public function receipt(string $id)
    {
        $payment = Payment::with(['invoice', 'receiver'])->findOrFail($id);

        if ($payment->statut !== 'valide') {
            return response()->json([
                'message' => 'Seuls les paiements validés peuvent avoir un reçu.',
            ], 422);
        }

        // Générer le PDF du reçu
        $pdf = \PDF::loadView('pdf.receipt', [
            'payment' => $payment,
            'school_name' => config('app.school_name'),
            'school_address' => config('app.school_address'),
            'school_phone' => config('app.school_phone'),
        ]);

        return $pdf->download("recu_{$payment->reference}.pdf");
    }

    /**
     * Statistiques des paiements
     */
    public function stats(Request $request)
    {
        $schoolYear = SchoolYear::current();
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Total collecté (validé)
        $totalCollected = Payment::validated()
            ->betweenDates($dateFrom, $dateTo)
            ->sum('montant');

        // Par mode de paiement
        $byMode = Payment::validated()
            ->betweenDates($dateFrom, $dateTo)
            ->select('mode_paiement', DB::raw('SUM(montant) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('mode_paiement')
            ->get()
            ->keyBy('mode_paiement');

        // Paiements en attente
        $pendingCount = Payment::where('statut', 'en_attente')->count();
        $pendingTotal = Payment::where('statut', 'en_attente')->sum('montant');

        // Évolution journalière
        $dailyPayments = Payment::validated()
            ->betweenDates($dateFrom, $dateTo)
            ->select(
                DB::raw('DATE(date_paiement) as date'),
                DB::raw('SUM(montant) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Stats facturation globales
        $totalInvoiced = Invoice::where('statut', '!=', 'annulee')->sum('montant_ttc');
        $totalOverdue = Invoice::overdue()->sum('solde');

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalCollected, // Alias pour le dashboard
            'total_collected' => $totalCollected,
            'total_pending' => $pendingTotal, // Alias pour le dashboard
            'total_overdue' => $totalOverdue,
            'by_mode' => $byMode,
            'pending' => [
                'count' => $pendingCount,
                'total' => $pendingTotal,
            ],
            'daily' => $dailyPayments,
        ]);
    }

    /**
     * Liste des impayés
     */
    public function unpaid(Request $request)
    {
        $query = Invoice::unpaid()
            ->with(['schoolYear:id,name'])
            ->select('invoices.*');

        // Filtrer par retard
        if ($request->get('overdue_only')) {
            $query->overdue();
        }

        // Tri par montant ou date
        $sortBy = $request->get('sort_by', 'date_echeance');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $invoices = $query->paginate($request->per_page ?? 50);

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
                $invoice->matricule = $student->matricule;
            } else {
                $invoice->student_name = 'Inconnu (' . $invoice->student_id . ')';
            }
            return $invoice;
        });

        // Stats globales
        $totalUnpaid = Invoice::unpaid()->sum('solde');
        $totalOverdue = Invoice::overdue()->sum('solde');

        return response()->json([
            'invoices' => $invoices,
            'stats' => [
                'total_unpaid' => $totalUnpaid,
                'total_overdue' => $totalOverdue,
                'count_unpaid' => Invoice::unpaid()->count(),
                'count_overdue' => Invoice::overdue()->count(),
            ],
        ]);
    }

    /**
     * Envoyer des rappels de paiement
     */
    public function sendReminders(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'uuid',
        ]);

        $sent = 0;
        $failed = 0;

        foreach ($validated['invoice_ids'] as $invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if (!$invoice || $invoice->solde <= 0) {
                continue;
            }

            // Récupérer les contacts (selon la base de données de l'élève)
            // TODO: Implémenter la récupération des contacts parents

            // Pour l'instant, log l'action
            AuditLog::log('payment_reminder_sent', Invoice::class, $invoiceId);
            $sent++;
        }

        return response()->json([
            'message' => "{$sent} rappel(s) envoyé(s).",
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }
}
