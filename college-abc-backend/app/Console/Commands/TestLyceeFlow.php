<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolYear;
use App\Models\Lycee\ClassLycee;
use App\Models\Lycee\StudentLycee;
use App\Models\Lycee\SubjectLycee;
use App\Models\Lycee\GradeLycee;
use App\Services\Lycee\ReportCardLyceeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestLyceeFlow extends Command
{
    protected $signature = 'test:lycee-flow';
    protected $description = 'Test complet du flux Lycée (Classe -> Note -> Bulletin) - Architecture Zero Erreur';

    public function handle()
    {
        try {
            $this->info("=== DÉBUT DU TEST D'INTÉGRATION LYCÉE ===");

            // 0. RESET COMPLET DE LA BASE LYCÉE
            $this->warn("Reset de la base school_lycee...");

            $this->call('migrate:fresh', [
                '--path' => 'database/migrations/lycee',
                '--database' => 'school_lycee',
                '--force' => true
            ]);

            // 1. SETUP ENVIRONNEMENT
            $year = SchoolYear::firstOrCreate(
                ['name' => '2024-2025'],
                ['start_date' => '2024-09-01', 'end_date' => '2025-06-30', 'is_current' => true]
            );
            $this->info("Année Scolaire: {$year->name}");

            // 2. CRÉATION CLASSE (2nde C)
            $classe = ClassLycee::create([
                'school_year_id' => $year->id,
                'niveau' => '2nde',
                'serie' => 'C',
                'nom' => 'Test-Integration',
                'seuil_minimum' => 15,
                'seuil_maximum' => 40,
                'effectif_actuel' => 0,
                'is_active' => true
            ]);
            $this->info("Classe Créée: {$classe->full_name} (ID: {$classe->id})");

            // 3. CRÉATION MATIÈRE (Maths)
            $maths = SubjectLycee::create([
                'code' => 'MAT-TEST',
                'nom' => 'Mathématiques Avancées',
                'coefficient_2nde' => 5, // Coeff fort
                'is_active' => true
            ]);
            $this->info("Matière Créée: {$maths->nom} (Coeff 2nde: {$maths->coefficient_2nde})");

            // 4. INSCRIPTION ÉLÈVE
            $eleve = StudentLycee::create([
                'matricule' => 'LYC-TEST-' . rand(1000, 9999),
                'nom' => 'KABORE',
                'prenoms' => 'Jean-Test',
                'date_naissance' => '2008-01-01',
                'lieu_naissance' => 'Ouagadougou',
                'sexe' => 'M',
                'serie' => 'C',
                'is_active' => true
            ]);
            $this->info("Élève Créé: {$eleve->full_name} ({$eleve->matricule})");

            // 5. SAISIE NOTES (15/20 DS, 12/20 Comp)
            GradeLycee::create([
                'school_year_id' => $year->id,
                'class_id' => $classe->id,
                'student_id' => $eleve->id,
                'subject_id' => $maths->id,
                'trimestre' => 1,
                'type_evaluation' => 'DS', // Devoir Surveillé
                'note_sur_20' => 15,
                'note_obtenue' => 15,
                'coefficient' => 1,
                'date_evaluation' => now(),
                'is_published' => true
            ]);

            GradeLycee::create([
                'school_year_id' => $year->id,
                'class_id' => $classe->id,
                'student_id' => $eleve->id,
                'subject_id' => $maths->id,
                'trimestre' => 1,
                'type_evaluation' => 'Comp', // Composition
                'note_sur_20' => 12,
                'note_obtenue' => 12,
                'coefficient' => 2,
                'date_evaluation' => now(),
                'is_published' => true
            ]);
            $this->info("Notes ajoutées : 15/20 (coeff 1), 12/20 (coeff 2)");

            // 6. GÉNÉRATION BULLETIN
            $this->info("--- Génération Bulletin T1 ---");
            $service = new ReportCardLyceeService();
            $bulletin = $service->generateForStudent($eleve->id, $classe->id, $year->id, 1);

            // 7. RÉSULTATS
            $this->table(
                ['Matière', 'Moyenne', 'Coeff', 'Points', 'Notes Détail'],
                collect($bulletin->data_matieres)->map(fn($m) => [
                    $m['nom'],
                    $m['moyenne'],
                    $m['coefficient'],
                    $m['points'],
                    json_encode($m['details'])
                ])
            );

            $this->info("Moyenne Générale: {$bulletin->moyenne_generale} / 20");
            $this->info("Total Points: {$bulletin->total_points}");

            if ($bulletin->moyenne_generale == 13.0) {
                $this->info("✅ SUCCÈS : Le calcul est exact (13.00 attendu).");
            } else {
                $this->error("❌ ÉCHEC : Calcul incorrect (Attendu 13.0, Reçu {$bulletin->moyenne_generale})");
            }
        } catch (\Throwable $e) {
            $this->error("FATAL ERROR: " . $e->getMessage());
            // $this->error($e->getTraceAsString());
        }
    }
}
