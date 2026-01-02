<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Entities\FeeType;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\Scholarship;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\Cycle;
use Modules\Academic\Entities\Level;

class FinanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Finance module...');

        // Get or create academic year
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2024-2025'],
            [
                'start_date' => '2024-09-01',
                'end_date' => '2025-07-31',
                'is_current' => true,
            ]
        );

        // Get cycles and levels
        $cycles = Cycle::all();
        $levels = Level::all();

        // Seed Fee Types
        $this->seedFeeTypes($cycles, $levels);

        // Seed Scholarships (if students exist)
        if (Student::count() > 0) {
            $this->seedScholarships($academicYear);
            
            // Seed Invoices
            $this->seedInvoices($academicYear);
            
            // Seed Payments
            $this->seedPayments($academicYear);
        } else {
            $this->command->warn('No students found. Skipping scholarships, invoices, and payments.');
        }

        $this->command->info('Finance module seeded successfully!');
    }

    /**
     * Seed fee types
     */
    protected function seedFeeTypes($cycles, $levels): void
    {
        $this->command->info('Seeding fee types...');

        $feeTypes = [
            // Universal fees (no cycle/level restriction)
            [
                'name' => 'Frais de scolarité',
                'description' => 'Frais de scolarité annuels',
                'amount' => 250000,
                'frequency' => 'annuel',
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Frais d\'inscription',
                'description' => 'Frais d\'inscription pour l\'année scolaire',
                'amount' => 50000,
                'frequency' => 'annuel',
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Frais de cantine',
                'description' => 'Frais de cantine mensuel',
                'amount' => 30000,
                'frequency' => 'mensuel',
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Frais de bibliothèque',
                'description' => 'Accès à la bibliothèque et ressources pédagogiques',
                'amount' => 15000,
                'frequency' => 'annuel',
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Frais de sport',
                'description' => 'Activités sportives et équipements',
                'amount' => 20000,
                'frequency' => 'annuel',
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Frais de transport',
                'description' => 'Transport scolaire mensuel',
                'amount' => 25000,
                'frequency' => 'mensuel',
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Frais d\'examen',
                'description' => 'Frais pour les examens officiels',
                'amount' => 35000,
                'frequency' => 'unique',
                'is_mandatory' => true,
                'is_active' => true,
            ],
        ];

        foreach ($feeTypes as $feeTypeData) {
            FeeType::firstOrCreate(
                ['name' => $feeTypeData['name']],
                $feeTypeData
            );
        }

        // Cycle-specific fees
        if ($cycles->isNotEmpty()) {
            $collegeCycle = $cycles->where('name', 'Collège')->first();
            if ($collegeCycle) {
                FeeType::firstOrCreate(
                    ['name' => 'Frais de laboratoire (Collège)'],
                    [
                        'description' => 'Accès aux laboratoires de sciences',
                        'amount' => 40000,
                        'frequency' => 'annuel',
                        'cycle_id' => $collegeCycle->id,
                        'is_mandatory' => true,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('✓ Fee types seeded');
    }

    /**
     * Seed scholarships
     */
    protected function seedScholarships(AcademicYear $academicYear): void
    {
        $this->command->info('Seeding scholarships...');

        $students = Student::inRandomOrder()->limit(10)->get();

        foreach ($students as $index => $student) {
            if ($index % 3 === 0) {
                // Percentage scholarship
                Scholarship::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                        'name' => 'Bourse d\'excellence',
                    ],
                    [
                        'type' => 'bourse',
                        'percentage' => [25, 50, 75][array_rand([25, 50, 75])],
                        'reason' => 'Excellence académique',
                        'start_date' => $academicYear->start_date,
                        'end_date' => $academicYear->end_date,
                        'status' => 'active',
                    ]
                );
            } elseif ($index % 3 === 1) {
                // Fixed amount scholarship
                Scholarship::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                        'name' => 'Réduction famille nombreuse',
                    ],
                    [
                        'type' => 'reduction',
                        'fixed_amount' => 50000,
                        'reason' => 'Famille nombreuse (3+ enfants inscrits)',
                        'start_date' => $academicYear->start_date,
                        'end_date' => $academicYear->end_date,
                        'status' => 'active',
                    ]
                );
            }
        }

        $this->command->info('✓ Scholarships seeded');
    }

    /**
     * Seed invoices
     */
    protected function seedInvoices(AcademicYear $academicYear): void
    {
        $this->command->info('Seeding invoices...');

        $students = Student::with('currentEnrollment')->limit(20)->get();
        $feeTypes = FeeType::active()->mandatory()->get();
        $adminUser = \Modules\Core\Entities\User::where('role', 'admin')->first() ?? \Modules\Core\Entities\User::first();

        foreach ($students as $student) {
            if (!$student->currentEnrollment) {
                continue;
            }

            // Create invoice for each trimester
            foreach (['trimestriel_1', 'trimestriel_2', 'trimestriel_3'] as $period) {
                $invoice = Invoice::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                        'period' => $period,
                    ],
                    [
                        'due_date' => now()->addDays(30),
                        'issue_date' => now(),
                        'status' => 'emise',
                        'generated_by' => $adminUser->id,
                        'generated_at' => now(),
                    ]
                );

                // Attach fee types
                foreach ($feeTypes as $feeType) {
                    if ($feeType->isApplicableToStudent($student)) {
                        $invoice->feeTypes()->syncWithoutDetaching([
                            $feeType->id => [
                                'base_amount' => $feeType->amount / 3, // Divided by 3 for trimester
                                'discount_amount' => 0,
                                'final_amount' => $feeType->amount / 3,
                                'quantity' => 1,
                            ]
                        ]);
                    }
                }

                // Recalculate balance (applies scholarships)
                $invoice->recalculateBalance();
            }
        }

        $this->command->info('✓ Invoices seeded');
    }

    /**
     * Seed payments
     */
    protected function seedPayments(AcademicYear $academicYear): void
    {
        $this->command->info('Seeding payments...');

        $invoices = Invoice::where('academic_year_id', $academicYear->id)
                          ->where('status', 'emise')
                          ->inRandomOrder()
                          ->limit(30)
                          ->get();

        $paymentMethods = ['especes', 'cheque', 'virement', 'mobile_money', 'carte'];
        $adminUser = \Modules\Core\Entities\User::where('role', 'admin')->first() ?? \Modules\Core\Entities\User::first();

        foreach ($invoices as $invoice) {
            // Random payment status: some fully paid, some partially, some unpaid
            $random = rand(1, 10);

            if ($random <= 3) {
                // Unpaid - no payment
                continue;
            } elseif ($random <= 7) {
                // Partially paid - 50% paid
                $amountToPay = $invoice->due_amount * 0.5;
            } else {
                // Fully paid
                $amountToPay = $invoice->due_amount;
            }

            // Get first fee type from invoice
            $feeType = $invoice->feeTypes->first();
            if (!$feeType) {
                continue;
            }

            Payment::firstOrCreate(
                [
                    'student_id' => $invoice->student_id,
                    'fee_type_id' => $feeType->id,
                    'academic_year_id' => $academicYear->id,
                    'payment_date' => now()->subDays(rand(1, 30))->toDateString(),
                ],
                [
                    'amount' => $amountToPay,
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'status' => 'valide',
                    'validated_by' => $adminUser->id,
                    'validated_at' => now(),
                    'payer_name' => 'Parent/Tuteur',
                ]
            );
        }

        $this->command->info('✓ Payments seeded');
    }
}
