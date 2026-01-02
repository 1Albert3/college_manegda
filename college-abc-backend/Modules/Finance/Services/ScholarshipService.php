<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\Scholarship;
use Modules\Finance\Entities\Invoice;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScholarshipService
{
    /**
     * Crée une nouvelle bourse
     *
     * @param array $data
     * @return Scholarship
     * @throws \Exception
     */
    public function createScholarship(array $data): Scholarship
    {
        $this->validateScholarshipData($data);

        DB::beginTransaction();
        
        try {
            // Vérifier que l'élève existe
            $student = Student::findOrFail($data['student_id']);
            
            // Vérifier l'année académique
            $academicYear = AcademicYear::findOrFail($data['academic_year_id']);

            // Vérifier qu'une bourse avec le même nom n'existe pas déjà pour cet élève
            $existing = Scholarship::where('student_id', $data['student_id'])
                                  ->where('academic_year_id', $data['academic_year_id'])
                                  ->where('name', $data['name'])
                                  ->whereIn('status', ['en_attente', 'active'])
                                  ->first();

            if ($existing) {
                throw new \Exception("Une bourse avec ce nom existe déjà pour cet élève");
            }

            // Créer la bourse
            $scholarship = Scholarship::create([
                'student_id' => $data['student_id'],
                'academic_year_id' => $data['academic_year_id'],
                'name' => $data['name'],
                'type' => $data['type'],
                'percentage' => $data['percentage'] ?? null,
                'fixed_amount' => $data['fixed_amount'] ?? null,
                'reason' => $data['reason'] ?? null,
                'conditions' => $data['conditions'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'] ?? 'en_attente',
                'notes' => $data['notes'] ?? null,
            ]);

            Log::info('Scholarship created', [
                'scholarship_id' => $scholarship->id,
                'student_id' => $student->id,
                'type' => $scholarship->type,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return $scholarship->fresh(['student', 'academicYear']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Scholarship creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Approuve une bourse
     *
     * @param Scholarship $scholarship
     * @return Scholarship
     * @throws \Exception
     */
    public function approveScholarship(Scholarship $scholarship): Scholarship
    {
        if ($scholarship->status !== 'en_attente') {
            throw new \Exception("Seules les bourses en attente peuvent être approuvées");
        }

        if (!auth()->check()) {
            throw new \Exception("Utilisateur non authentifié");
        }

        DB::beginTransaction();
        
        try {
            $scholarship->approve(auth()->user());

            Log::info('Scholarship approved', [
                'scholarship_id' => $scholarship->id,
                'approved_by' => auth()->id(),
            ]);

            DB::commit();

            return $scholarship->fresh(['student', 'academicYear', 'approver']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Scholarship approval failed', [
                'scholarship_id' => $scholarship->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Applique une bourse à toutes les factures d'un élève
     *
     * @param Scholarship $scholarship
     * @return array
     * @throws \Exception
     */
    public function applyScholarship(Scholarship $scholarship): array
    {
        if (!$scholarship->is_active) {
            throw new \Exception("La bourse n'est pas active");
        }

        DB::beginTransaction();
        
        try {
            // Récupérer toutes les factures de l'élève pour l'année académique
            $invoices = Invoice::where('student_id', $scholarship->student_id)
                              ->where('academic_year_id', $scholarship->academic_year_id)
                              ->whereNotIn('status', ['brouillon', 'annulee'])
                              ->get();

            $updatedCount = 0;
            $totalDiscountApplied = 0;

            foreach ($invoices as $invoice) {
                $oldDiscount = $invoice->discount_amount;
                $invoice->recalculateBalance();
                $newDiscount = $invoice->discount_amount;
                
                if ($newDiscount != $oldDiscount) {
                    $updatedCount++;
                    $totalDiscountApplied += ($newDiscount - $oldDiscount);
                }
            }

            Log::info('Scholarship applied to invoices', [
                'scholarship_id' => $scholarship->id,
                'invoices_updated' => $updatedCount,
                'total_discount' => $totalDiscountApplied,
            ]);

            DB::commit();

            return [
                'scholarship_id' => $scholarship->id,
                'invoices_updated' => $updatedCount,
                'total_discount_applied' => $totalDiscountApplied,
                'invoices' => $invoices->fresh(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Scholarship application failed', [
                'scholarship_id' => $scholarship->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcule la réduction pour un montant donné
     *
     * @param Scholarship $scholarship
     * @param float $amount
     * @return float
     */
    public function calculateDiscount(Scholarship $scholarship, float $amount): float
    {
        if (!$scholarship->is_active) {
            return 0;
        }

        return $scholarship->calculateDiscountAmount($amount);
    }

    /**
     * Suspend une bourse
     *
     * @param Scholarship $scholarship
     * @param string|null $reason
     * @return Scholarship
     * @throws \Exception
     */
    public function suspendScholarship(Scholarship $scholarship, ?string $reason = null): Scholarship
    {
        if ($scholarship->status !== 'active') {
            throw new \Exception("Seules les bourses actives peuvent être suspendues");
        }

        DB::beginTransaction();
        
        try {
            if ($reason) {
                $scholarship->notes = ($scholarship->notes ? $scholarship->notes . "\n" : '') 
                                     . "Suspendue le " . now()->format('d/m/Y H:i') 
                                     . " - Raison: " . $reason;
            }

            $scholarship->suspend();

            // Recalculer les factures
            $this->recalculateStudentInvoices($scholarship);

            Log::warning('Scholarship suspended', [
                'scholarship_id' => $scholarship->id,
                'reason' => $reason,
                'suspended_by' => auth()->id(),
            ]);

            DB::commit();

            return $scholarship->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Réactive une bourse suspendue
     *
     * @param Scholarship $scholarship
     * @return Scholarship
     * @throws \Exception
     */
    public function reactivateScholarship(Scholarship $scholarship): Scholarship
    {
        if ($scholarship->status !== 'suspendue') {
            throw new \Exception("Seules les bourses suspendues peuvent être réactivées");
        }

        DB::beginTransaction();
        
        try {
            $scholarship->reactivate();

            // Recalculer les factures
            $this->recalculateStudentInvoices($scholarship);

            Log::info('Scholarship reactivated', [
                'scholarship_id' => $scholarship->id,
                'reactivated_by' => auth()->id(),
            ]);

            DB::commit();

            return $scholarship->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Annule une bourse
     *
     * @param Scholarship $scholarship
     * @param string|null $reason
     * @return Scholarship
     * @throws \Exception
     */
    public function cancelScholarship(Scholarship $scholarship, ?string $reason = null): Scholarship
    {
        if ($scholarship->status === 'annulee') {
            throw new \Exception("Cette bourse est déjà annulée");
        }

        DB::beginTransaction();
        
        try {
            if ($reason) {
                $scholarship->notes = ($scholarship->notes ? $scholarship->notes . "\n" : '') 
                                     . "Annulée le " . now()->format('d/m/Y H:i') 
                                     . " - Raison: " . $reason;
            }

            $scholarship->cancel();

            // Recalculer les factures
            $this->recalculateStudentInvoices($scholarship);

            Log::warning('Scholarship cancelled', [
                'scholarship_id' => $scholarship->id,
                'reason' => $reason,
                'cancelled_by' => auth()->id(),
            ]);

            DB::commit();

            return $scholarship->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupère toutes les bourses actives d'un élève
     *
     * @param int $studentId
     * @param int|null $academicYearId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStudentActiveScholarships(int $studentId, ?int $academicYearId = null)
    {
        $query = Scholarship::where('student_id', $studentId)
                           ->active()
                           ->with(['academicYear', 'approver']);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->get();
    }

    /**
     * Récupère les statistiques de bourses
     *
     * @param array $filters
     * @return array
     */
    public function getScholarshipStatistics(array $filters = []): array
    {
        $query = Scholarship::query();

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $totalScholarships = (clone $query)->count();
        $activeScholarships = (clone $query)->active()->count();
        $pendingScholarships = (clone $query)->pending()->count();

        // Calculer le montant total des réductions accordées
        $totalDiscountValue = 0;
        $scholarships = (clone $query)->active()->get();
        
        foreach ($scholarships as $scholarship) {
            if ($scholarship->fixed_amount) {
                $totalDiscountValue += $scholarship->fixed_amount;
            }
            // Pour les pourcentages, on devrait calculer sur les factures réelles
        }

        // Par type
        $byType = (clone $query)->active()
                                ->select('type', DB::raw('COUNT(*) as count'))
                                ->groupBy('type')
                                ->get();

        // Par statut
        $byStatus = (clone $query)->select('status', DB::raw('COUNT(*) as count'))
                                  ->groupBy('status')
                                  ->get();

        return [
            'summary' => [
                'total_scholarships' => $totalScholarships,
                'active_scholarships' => $activeScholarships,
                'pending_scholarships' => $pendingScholarships,
                'total_discount_value' => $totalDiscountValue,
            ],
            'by_type' => $byType,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Vérifie et expire les bourses expirées
     *
     * @return int Nombre de bourses expirées
     */
    public function checkAndExpireScholarships(): int
    {
        $expiredCount = 0;

        $scholarships = Scholarship::where('status', 'active')
                                  ->where('end_date', '<', now())
                                  ->get();

        foreach ($scholarships as $scholarship) {
            DB::transaction(function() use ($scholarship) {
                $scholarship->checkExpiration();
                $this->recalculateStudentInvoices($scholarship);
            });
            $expiredCount++;
        }

        if ($expiredCount > 0) {
            Log::info('Scholarships expired', [
                'count' => $expiredCount,
                'date' => now(),
            ]);
        }

        return $expiredCount;
    }

    /**
     * Met à jour une bourse
     *
     * @param Scholarship $scholarship
     * @param array $data
     * @return Scholarship
     * @throws \Exception
     */
    public function updateScholarship(Scholarship $scholarship, array $data): Scholarship
    {
        // Ne pas permettre la modification de bourses expirées ou annulées
        if (in_array($scholarship->status, ['expiree', 'annulee'])) {
            throw new \Exception("Impossible de modifier une bourse expirée ou annulée");
        }

        DB::beginTransaction();
        
        try {
            $scholarship->update($data);

            // Si le montant/pourcentage a changé, recalculer les factures
            if (isset($data['percentage']) || isset($data['fixed_amount'])) {
                $this->recalculateStudentInvoices($scholarship);
            }

            Log::info('Scholarship updated', [
                'scholarship_id' => $scholarship->id,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return $scholarship->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Recalcule toutes les factures d'un élève suite à modification de bourse
     *
     * @param Scholarship $scholarship
     */
    protected function recalculateStudentInvoices(Scholarship $scholarship): void
    {
        $invoices = Invoice::where('student_id', $scholarship->student_id)
                          ->where('academic_year_id', $scholarship->academic_year_id)
                          ->whereNotIn('status', ['brouillon', 'annulee'])
                          ->get();

        foreach ($invoices as $invoice) {
            $invoice->recalculateBalance();
        }
    }

    /**
     * Valide les données de bourse
     *
     * @param array $data
     * @throws \Exception
     */
    protected function validateScholarshipData(array $data): void
    {
        $required = ['student_id', 'academic_year_id', 'name', 'type', 'start_date', 'end_date'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Le champ '{$field}' est requis");
            }
        }

        // Vérifier qu'au moins un type de réduction est spécifié
        if (empty($data['percentage']) && empty($data['fixed_amount'])) {
            throw new \Exception("Veuillez spécifier un pourcentage ou un montant fixe");
        }

        // Ne pas autoriser les deux en même temps
        if (!empty($data['percentage']) && !empty($data['fixed_amount'])) {
            throw new \Exception("Veuillez spécifier soit un pourcentage, soit un montant fixe, pas les deux");
        }

        // Valider le pourcentage
        if (!empty($data['percentage']) && ($data['percentage'] < 0 || $data['percentage'] > 100)) {
            throw new \Exception("Le pourcentage doit être entre 0 et 100");
        }

        // Valider le montant fixe
        if (!empty($data['fixed_amount']) && $data['fixed_amount'] < 0) {
            throw new \Exception("Le montant fixe doit être positif");
        }

        // Valider les dates
        if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
            throw new \Exception("La date de début doit être antérieure à la date de fin");
        }

        $validTypes = ['bourse', 'reduction', 'exoneration', 'aide_sociale'];
        if (!in_array($data['type'], $validTypes)) {
            throw new \Exception("Type de bourse invalide");
        }
    }
}
