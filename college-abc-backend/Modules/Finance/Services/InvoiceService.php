<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\FeeType;
use Modules\Finance\Entities\Scholarship;
use Modules\Finance\Entities\PaymentReminder;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exceptions\BusinessException;

class InvoiceService
{
    /**
     * Génère une nouvelle facture pour un élève
     *
     * @param array $data
     * @return Invoice
     * @throws BusinessException
     * @throws \Exception
     */
    public function generateInvoice(array $data): Invoice
    {
        $this->validateInvoiceData($data);

        DB::beginTransaction();
        
        try {
            // Vérifier que l'élève existe et est inscrit
            $student = Student::findOrFail($data['student_id']);
            $enrollment = $student->currentEnrollment;
            
            if (!$enrollment) {
                throw new BusinessException("L'élève n'est pas inscrit pour l'année en cours");
            }

            // Vérifier l'année académique
            $academicYear = AcademicYear::findOrFail($data['academic_year_id']);

            // Vérifier qu'une facture similaire n'existe pas déjà
            $existingInvoice = Invoice::where('student_id', $data['student_id'])
                                      ->where('academic_year_id', $data['academic_year_id'])
                                      ->where('period', $data['period'])
                                      ->whereNotIn('status', ['annulee'])
                                      ->first();

            if ($existingInvoice) {
                throw new BusinessException("Une facture existe déjà pour cette période");
            }

            // Créer la facture
            $invoice = Invoice::create([
                'student_id' => $data['student_id'],
                'academic_year_id' => $data['academic_year_id'],
                'period' => $data['period'],
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'issue_date' => $data['issue_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'status' => 'brouillon',
                'generated_by' => auth()->id(),
                'generated_at' => now(),
            ]);

            // Ajouter les types de frais applicables
            if (isset($data['fee_types']) && is_array($data['fee_types'])) {
                foreach ($data['fee_types'] as $feeTypeData) {
                    $feeType = FeeType::findOrFail($feeTypeData['fee_type_id']);
                    
                    // Vérifier que le frais est applicable à cet élève
                    if (!$feeType->isApplicableToStudent($student)) {
                        Log::warning("Fee type not applicable", [
                            'fee_type_id' => $feeType->id,
                            'student_id' => $student->id,
                        ]);
                        continue;
                    }

                    $quantity = $feeTypeData['quantity'] ?? 1;
                    $discount = $feeTypeData['discount'] ?? 0;

                    $invoice->addFeeType($feeType, $quantity, $discount);
                }
            } else {
                // Ajouter automatiquement tous les frais obligatoires applicables
                $this->addApplicableFees($invoice, $student);
            }

            // Recalculer le solde (applique automatiquement les bourses)
            $invoice->recalculateBalance();

            // Créer des rappels de paiement automatiques si la facture est émise
            if (isset($data['auto_issue']) && $data['auto_issue']) {
                $invoice->issue();
                $this->createPaymentReminders($invoice);
            }

            Log::info('Invoice generated', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'student_id' => $student->id,
                'total_amount' => $invoice->total_amount,
            ]);

            DB::commit();

            return $invoice->fresh(['student', 'academicYear', 'feeTypes', 'scholarships']);

        } catch (BusinessException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice generation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Calcule le total dû pour un élève
     *
     * @param int $studentId
     * @param int $academicYearId
     * @param string|null $period
     * @return array
     */
    public function calculateTotalDue(int $studentId, int $academicYearId, ?string $period = null): array
    {
        $student = Student::findOrFail($studentId);
        $enrollment = $student->currentEnrollment;

        if (!$enrollment) {
            throw new \Exception("L'élève n'est pas inscrit");
        }

        // Récupérer tous les types de frais applicables
        $applicableFees = FeeType::active()
                                 ->where(function($q) use ($enrollment) {
                                     $q->whereNull('cycle_id')
                                       ->orWhere('cycle_id', $enrollment->classRoom->cycle_id);
                                 })
                                 ->where(function($q) use ($enrollment) {
                                     $q->whereNull('level_id')
                                       ->orWhere('level_id', $enrollment->classRoom->level_id);
                                 })
                                 ->get();

        // Calculer le montant total
        $totalAmount = 0;
        $feeBreakdown = [];

        foreach ($applicableFees as $feeType) {
            $amount = $feeType->calculateAmountForPeriod($period ?? 'annuel');
            $totalAmount += $amount;
            
            $feeBreakdown[] = [
                'fee_type_id' => $feeType->id,
                'name' => $feeType->name,
                'amount' => $amount,
                'is_mandatory' => $feeType->is_mandatory,
            ];
        }

        // Calculer les réductions de bourses
        $scholarships = Scholarship::where('student_id', $studentId)
                                  ->where('academic_year_id', $academicYearId)
                                  ->active()
                                  ->get();

        $totalDiscount = 0;
        $scholarshipBreakdown = [];

        foreach ($scholarships as $scholarship) {
            $discount = $scholarship->calculateDiscountAmount($totalAmount);
            $totalDiscount += $discount;
            
            $scholarshipBreakdown[] = [
                'scholarship_id' => $scholarship->id,
                'name' => $scholarship->name,
                'type' => $scholarship->type,
                'discount' => $discount,
            ];
        }

        // Calculer le montant déjà payé
        $totalPaid = \Modules\Finance\Entities\Payment::where('student_id', $studentId)
                                                      ->where('academic_year_id', $academicYearId)
                                                      ->validated()
                                                      ->sum('amount');

        $netAmount = $totalAmount - $totalDiscount;
        $remainingDue = $netAmount - $totalPaid;

        return [
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'period' => $period ?? 'annuel',
            'total_amount' => $totalAmount,
            'total_discount' => $totalDiscount,
            'net_amount' => $netAmount,
            'total_paid' => $totalPaid,
            'remaining_due' => $remainingDue,
            'fee_breakdown' => $feeBreakdown,
            'scholarship_breakdown' => $scholarshipBreakdown,
        ];
    }

    /**
     * Applique une bourse à une facture
     *
     * @param Invoice $invoice
     * @param Scholarship $scholarship
     * @return Invoice
     * @throws \Exception
     */
    public function applyScholarship(Invoice $invoice, Scholarship $scholarship): Invoice
    {
        if ($invoice->student_id !== $scholarship->student_id) {
            throw new \Exception("La bourse n'appartient pas à cet élève");
        }

        if ($invoice->academic_year_id !== $scholarship->academic_year_id) {
            throw new \Exception("La bourse n'est pas valide pour cette année académique");
        }

        if (!$scholarship->is_active) {
            throw new \Exception("La bourse n'est pas active");
        }

        DB::beginTransaction();
        
        try {
            $invoice->recalculateBalance();

            Log::info('Scholarship applied to invoice', [
                'invoice_id' => $invoice->id,
                'scholarship_id' => $scholarship->id,
                'discount_amount' => $invoice->discount_amount,
            ]);

            DB::commit();

            return $invoice->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupère toutes les factures impayées
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnpaidInvoices(array $filters = [])
    {
        $query = Invoice::unpaid()
                       ->with(['student', 'academicYear']);

        // Filtre par année académique
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        // Filtre par classe
        if (isset($filters['class_id'])) {
            $query->whereHas('student.currentEnrollment', function($q) use ($filters) {
                $q->where('class_id', $filters['class_id']);
            });
        }

        // Filtre par statut spécifique
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtre par période
        if (isset($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        // Factures en retard uniquement
        if (isset($filters['overdue_only']) && $filters['overdue_only']) {
            $query->overdue();
        }

        // Factures dues bientôt
        if (isset($filters['due_soon_days'])) {
            $query->dueSoon($filters['due_soon_days']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'due_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Émet une facture (la passe de brouillon à émise)
     *
     * @param Invoice $invoice
     * @param bool $createReminders
     * @return Invoice
     * @throws \Exception
     */
    public function issueInvoice(Invoice $invoice, bool $createReminders = true): Invoice
    {
        if ($invoice->status !== 'brouillon') {
            throw new \Exception("Seules les factures en brouillon peuvent être émises");
        }

        if ($invoice->due_amount <= 0) {
            throw new \Exception("La facture est déjà payée");
        }

        DB::beginTransaction();
        
        try {
            $invoice->issue();

            if ($createReminders) {
                $this->createPaymentReminders($invoice);
            }

            Log::info('Invoice issued', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'due_amount' => $invoice->due_amount,
            ]);

            DB::commit();

            return $invoice->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Génère un PDF de facture
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function generateInvoicePDF(Invoice $invoice)
    {
        // Charger toutes les relations nécessaires
        $invoice->load(['student', 'academicYear', 'feeTypes', 'scholarships']);

        $data = [
            'invoice' => $invoice,
            'student' => $invoice->student,
            'academicYear' => $invoice->academicYear,
            'college' => [
                'name' => config('app.name', 'Collège Wend-Manegda'),
                'address' => config('college.address', 'Ouagadougou, Burkina Faso'),
                'phone' => config('college.phone', '+226 XX XX XX XX'),
                'email' => config('college.email', 'contact@college-manegda.bf'),
            ],
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('finance::pdf.invoice', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("facture_{$invoice->invoice_number}.pdf");
    }

    /**
     * Annule une facture
     *
     * @param Invoice $invoice
     * @param string|null $reason
     * @return Invoice
     * @throws \Exception
     */
    public function cancelInvoice(Invoice $invoice, ?string $reason = null): Invoice
    {
        if ($invoice->status === 'annulee') {
            throw new \Exception("Cette facture est déjà annulée");
        }

        if ($invoice->paid_amount > 0) {
            throw new \Exception("Impossible d'annuler une facture partiellement payée");
        }

        DB::beginTransaction();
        
        try {
            if ($reason) {
                $invoice->notes = ($invoice->notes ? $invoice->notes . "\n" : '') 
                                 . "Annulée le " . now()->format('d/m/Y H:i') 
                                 . " - Raison: " . $reason;
            }

            $invoice->cancel();

            // Annuler les rappels planifiés
            PaymentReminder::where('invoice_id', $invoice->id)
                          ->where('status', 'planifie')
                          ->update(['status' => 'annule']);

            Log::warning('Invoice cancelled', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'reason' => $reason,
            ]);

            DB::commit();

            return $invoice->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupère les statistiques de facturation
     *
     * @param array $filters
     * @return array
     */
    public function getInvoiceStatistics(array $filters = []): array
    {
        $query = Invoice::query();

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        $total = (clone $query)->whereNotIn('status', ['brouillon', 'annulee'])->sum('total_amount');
        $paid = (clone $query)->paid()->sum('total_amount');
        $unpaid = (clone $query)->unpaid()->sum('due_amount');
        $overdue = (clone $query)->overdue()->sum('due_amount');

        $invoiceCount = (clone $query)->whereNotIn('status', ['brouillon', 'annulee'])->count();
        $paidCount = (clone $query)->paid()->count();
        $unpaidCount = (clone $query)->unpaid()->count();

        return [
            'summary' => [
                'total_invoiced' => $total,
                'total_paid' => $paid,
                'total_unpaid' => $unpaid,
                'total_overdue' => $overdue,
                'collection_rate' => $total > 0 ? round(($paid / $total) * 100, 2) : 0,
            ],
            'counts' => [
                'total_invoices' => $invoiceCount,
                'paid_invoices' => $paidCount,
                'unpaid_invoices' => $unpaidCount,
            ],
        ];
    }

    /**
     * Ajoute automatiquement les frais applicables à une facture
     *
     * @param Invoice $invoice
     * @param Student $student
     */
    protected function addApplicableFees(Invoice $invoice, Student $student): void
    {
        $enrollment = $student->currentEnrollment;
        
        if (!$enrollment) {
            return;
        }

        $applicableFees = FeeType::active()
                                ->mandatory()
                                ->where(function($q) use ($enrollment) {
                                    $q->whereNull('cycle_id')
                                      ->orWhere('cycle_id', $enrollment->classRoom->cycle_id);
                                })
                                ->where(function($q) use ($enrollment) {
                                    $q->whereNull('level_id')
                                      ->orWhere('level_id', $enrollment->classRoom->level_id);
                                })
                                ->get();

        foreach ($applicableFees as $feeType) {
            $invoice->addFeeType($feeType);
        }
    }

    /**
     * Crée des rappels de paiement pour une facture
     *
     * @param Invoice $invoice
     */
    protected function createPaymentReminders(Invoice $invoice): void
    {
        // Rappel 7 jours avant échéance
        PaymentReminder::createForInvoice($invoice, 'sms', 7);
        
        // Rappel 3 jours avant échéance
        PaymentReminder::createForInvoice($invoice, 'sms', 3);
        
        // Rappel le jour de l'échéance
        PaymentReminder::createForInvoice($invoice, 'sms', 0);
    }

    /**
     * Valide les données de facture
     *
     * @param array $data
     * @throws \Exception
     */
    protected function validateInvoiceData(array $data): void
    {
        $required = ['student_id', 'academic_year_id', 'period'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Le champ '{$field}' est requis");
            }
        }

        $validPeriods = ['annuel', 'trimestriel_1', 'trimestriel_2', 'trimestriel_3', 'mensuel'];
        if (!in_array($data['period'], $validPeriods)) {
            throw new \Exception("Période invalide");
        }
    }
}
