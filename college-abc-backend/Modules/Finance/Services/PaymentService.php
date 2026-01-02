<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\FeeType;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentService
{
    /**
     * Enregistre un nouveau paiement
     *
     * @param array $data
     * @return Payment
     * @throws \Exception
     */
    public function recordPayment(array $data): Payment
    {
        // Validation des données
        $this->validatePaymentData($data);

        DB::beginTransaction();
        
        try {
            // Vérifier que l'élève existe
            $student = Student::findOrFail($data['student_id']);
            
            // Vérifier que le type de frais existe et est actif
            $feeType = FeeType::findOrFail($data['fee_type_id']);
            if (!$feeType->is_active) {
                throw new \Exception("Le type de frais '{$feeType->name}' n'est plus actif.");
            }

            // Vérifier que l'année académique existe
            $academicYear = AcademicYear::findOrFail($data['academic_year_id']);

            // Créer le paiement
            $payment = Payment::create([
                'student_id' => $data['student_id'],
                'fee_type_id' => $data['fee_type_id'],
                'academic_year_id' => $data['academic_year_id'],
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now(),
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'valide',
            ]);

            // Si le paiement est marqué comme validé, l'assigner à l'utilisateur actuel
            if ($payment->status === 'valide' && auth()->check()) {
                $payment->validated_by = auth()->id();
                $payment->validated_at = now();
                $payment->save();
            }

            // Log de l'opération
            Log::info('Payment recorded', [
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'student_id' => $student->id,
                'amount' => $payment->amount,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return $payment->fresh(['student', 'feeType', 'academicYear', 'validator']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment recording failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Génère un reçu PDF pour un paiement
     *
     * @param Payment $payment
     * @return \Illuminate\Http\Response
     */
    public function generateReceipt(Payment $payment)
    {
        // Charger les relations nécessaires
        $payment->load(['student', 'feeType', 'academicYear', 'validator']);

        // Préparer les données pour le PDF
        $data = [
            'payment' => $payment,
            'student' => $payment->student,
            'feeType' => $payment->feeType,
            'academicYear' => $payment->academicYear,
            'college' => [
                'name' => config('app.name', 'Collège Wend-Manegda'),
                'address' => config('college.address', 'Ouagadougou, Burkina Faso'),
                'phone' => config('college.phone', '+226 XX XX XX XX'),
                'email' => config('college.email', 'contact@college-manegda.bf'),
            ],
            'generated_at' => now(),
        ];

        // Générer le PDF
        $pdf = Pdf::loadView('finance::pdf.receipt', $data);
        
        // Configurer le PDF
        $pdf->setPaper('a5', 'portrait');

        return $pdf->stream("recu_{$payment->receipt_number}.pdf");
    }

    /**
     * Calcule le solde d'un élève pour une année académique
     *
     * @param int $studentId
     * @param int|null $academicYearId
     * @return array
     */
    public function calculateBalance(int $studentId, ?int $academicYearId = null): array
    {
        // Si aucune année académique n'est spécifiée, utiliser l'année courante
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        if (!$academicYearId) {
            throw new \Exception("Aucune année académique active trouvée");
        }

        // Récupérer toutes les factures de l'élève pour cette année
        $invoices = Invoice::where('student_id', $studentId)
                          ->where('academic_year_id', $academicYearId)
                          ->whereNotIn('status', ['brouillon', 'annulee'])
                          ->get();

        // Calculer les totaux
        $totalDue = $invoices->sum('total_amount');
        $totalDiscount = $invoices->sum('discount_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalRemaining = $invoices->sum('due_amount');

        // Récupérer les paiements validés
        $payments = Payment::where('student_id', $studentId)
                          ->where('academic_year_id', $academicYearId)
                          ->validated()
                          ->orderBy('payment_date', 'desc')
                          ->get();

        // Récupérer les bourses actives
        $scholarships = \Modules\Finance\Entities\Scholarship::where('student_id', $studentId)
                                                              ->where('academic_year_id', $academicYearId)
                                                              ->active()
                                                              ->get();

        return [
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'summary' => [
                'total_due' => $totalDue,
                'total_discount' => $totalDiscount,
                'total_paid' => $totalPaid,
                'total_remaining' => $totalRemaining,
                'payment_progress' => $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 2) : 0,
            ],
            'invoices_count' => $invoices->count(),
            'payments_count' => $payments->count(),
            'scholarships_count' => $scholarships->count(),
            'invoices' => $invoices,
            'payments' => $payments,
            'scholarships' => $scholarships,
        ];
    }

    /**
     * Récupère l'historique des paiements d'un élève
     *
     * @param int $studentId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStudentPaymentHistory(int $studentId, array $filters = [])
    {
        $query = Payment::where('student_id', $studentId)
                       ->with(['feeType', 'academicYear', 'validator']);

        // Filtre par année académique
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtre par méthode de paiement
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Filtre par plage de dates
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('payment_date', [$filters['start_date'], $filters['end_date']]);
        }

        // Filtre par type de frais
        if (isset($filters['fee_type_id'])) {
            $query->where('fee_type_id', $filters['fee_type_id']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'payment_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Valide un paiement en attente
     *
     * @param Payment $payment
     * @return Payment
     * @throws \Exception
     */
    public function validatePayment(Payment $payment): Payment
    {
        if ($payment->status !== 'en_attente') {
            throw new \Exception("Seuls les paiements en attente peuvent être validés");
        }

        if (!auth()->check()) {
            throw new \Exception("Utilisateur non authentifié");
        }

        DB::beginTransaction();
        
        try {
            $payment->validate(auth()->user());

            Log::info('Payment validated', [
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'validated_by' => auth()->id(),
            ]);

            DB::commit();

            return $payment->fresh(['student', 'feeType', 'validator']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment validation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Annule un paiement
     *
     * @param Payment $payment
     * @param string|null $reason
     * @return Payment
     * @throws \Exception
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): Payment
    {
        if ($payment->status === 'annule') {
            throw new \Exception("Ce paiement est déjà annulé");
        }

        DB::beginTransaction();
        
        try {
            if ($reason) {
                $payment->notes = ($payment->notes ? $payment->notes . "\n" : '') 
                                 . "Annulé le " . now()->format('d/m/Y H:i') 
                                 . " - Raison: " . $reason;
            }

            $payment->cancel();

            Log::warning('Payment cancelled', [
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'reason' => $reason,
                'cancelled_by' => auth()->id(),
            ]);

            DB::commit();

            return $payment->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment cancellation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Récupère les statistiques de paiements
     *
     * @param array $filters
     * @return array
     */
    public function getPaymentStatistics(array $filters = []): array
    {
        $query = Payment::query();

        // Filtres
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('payment_date', [$filters['start_date'], $filters['end_date']]);
        }

        // Statistiques globales
        $totalPayments = (clone $query)->validated()->sum('amount');
        $paymentCount = (clone $query)->validated()->count();
        $averagePayment = $paymentCount > 0 ? $totalPayments / $paymentCount : 0;

        // Par méthode de paiement
        $byMethod = (clone $query)->validated()
                                  ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                                  ->groupBy('payment_method')
                                  ->get();

        // Par type de frais
        $byFeeType = (clone $query)->validated()
                                   ->join('fee_types', 'payments.fee_type_id', '=', 'fee_types.id')
                                   ->select('fee_types.name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
                                   ->groupBy('fee_types.id', 'fee_types.name')
                                   ->get();

        return [
            'summary' => [
                'total_amount' => $totalPayments,
                'payment_count' => $paymentCount,
                'average_payment' => round($averagePayment, 2),
            ],
            'by_method' => $byMethod,
            'by_fee_type' => $byFeeType,
        ];
    }

    /**
     * Valide les données de paiement
     *
     * @param array $data
     * @throws \Exception
     */
    protected function validatePaymentData(array $data): void
    {
        $required = ['student_id', 'fee_type_id', 'academic_year_id', 'amount', 'payment_method'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Le champ '{$field}' est requis");
            }
        }

        if ($data['amount'] <= 0) {
            throw new \Exception("Le montant doit être supérieur à 0");
        }

        $validMethods = ['especes', 'cheque', 'virement', 'mobile_money', 'carte'];
        if (!in_array($data['payment_method'], $validMethods)) {
            throw new \Exception("Méthode de paiement invalide");
        }
    }
}
