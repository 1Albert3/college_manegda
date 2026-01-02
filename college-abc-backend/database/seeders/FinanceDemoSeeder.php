<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Finance\FeeStructure;
use App\Models\Finance\Invoice;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Str;

class FinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::where('is_current', true)->first() ?? SchoolYear::first();
        if (!$schoolYear) return;

        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        // 1. Structures Tarifaires Prototypiques (Burkina Faso)
        $structures = [
            ['cycle' => 'college', 'niveau' => '6ème', 'inscription' => 15000, 'scolarite' => 65000, 'apee' => 5000],
            ['cycle' => 'college', 'niveau' => '3ème', 'inscription' => 20000, 'scolarite' => 75000, 'apee' => 5000],
            ['cycle' => 'lycee', 'niveau' => '2nde C', 'inscription' => 25000, 'scolarite' => 110000, 'apee' => 5000],
            ['cycle' => 'lycee', 'niveau' => 'Tle D', 'inscription' => 30000, 'scolarite' => 125000, 'apee' => 5000],
        ];

        foreach ($structures as $s) {
            FeeStructure::updateOrCreate(
                ['school_year_id' => $schoolYear->id, 'cycle' => $s['cycle'], 'niveau' => $s['niveau']],
                [
                    'inscription' => $s['inscription'],
                    'scolarite' => $s['scolarite'],
                    'apee' => $s['apee'],
                    'total' => $s['inscription'] + $s['scolarite'] + $s['apee'],
                    'reduction_frere_soeur' => 10,
                    'is_active' => true
                ]
            );
        }

        // 2. Quelques factures de démo avec IDs fixes pour idempotence
        $demoInvoices = [
            [
                'number' => 'FAC-DEMO-001',
                'student_id' => '11111111-1111-1111-1111-111111111111',
                'student_database' => 'school_college',
                'type' => 'scolarite',
                'description' => 'Scolarité Annuelle 6ème',
                'montant_ttc' => 85000,
                'statut' => 'emise',
            ],
            [
                'number' => 'FAC-DEMO-002',
                'student_id' => '22222222-2222-2222-2222-222222222222',
                'student_database' => 'school_lycee',
                'type' => 'scolarite',
                'description' => 'Scolarité Annuelle Tle D',
                'montant_ttc' => 160000,
                'statut' => 'partiellement_payee',
                'montant_paye' => 60000,
            ],
            [
                'number' => 'FAC-DEMO-003',
                'student_id' => '33333333-3333-3333-3333-333333333333',
                'student_database' => 'school_mp',
                'type' => 'inscription',
                'description' => 'Frais d\'inscription CP1',
                'montant_ttc' => 25000,
                'statut' => 'payee',
                'montant_paye' => 25000,
            ]
        ];

        foreach ($demoInvoices as $inv) {
            $existing = Invoice::where('student_id', $inv['student_id'])
                ->where('description', $inv['description'])
                ->where('school_year_id', $schoolYear->id)
                ->first();

            if (!$existing) {
                Invoice::create([
                    'number' => $inv['number'],
                    'student_id' => $inv['student_id'],
                    'student_database' => $inv['student_database'],
                    'school_year_id' => $schoolYear->id,
                    'type' => $inv['type'],
                    'description' => $inv['description'],
                    'montant_ht' => $inv['montant_ttc'] * 0.82, // Simulation
                    'montant_ttc' => $inv['montant_ttc'],
                    'montant_paye' => $inv['montant_paye'] ?? 0,
                    'solde' => $inv['montant_ttc'] - ($inv['montant_paye'] ?? 0),
                    'statut' => $inv['statut'],
                    'date_emission' => now()->subDays(rand(1, 30)),
                    'date_echeance' => now()->addDays(30),
                    'created_by' => $admin->id
                ]);
            }
        }
    }
}
