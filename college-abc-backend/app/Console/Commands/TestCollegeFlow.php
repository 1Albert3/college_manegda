<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolYear;
use App\Models\College\ClassCollege;
use App\Models\College\StudentCollege;
use App\Models\College\SubjectCollege;
use App\Models\College\GradeCollege;
use App\Services\College\ReportCardCollegeService;
use Illuminate\Support\Facades\DB;

class TestCollegeFlow extends Command
{
    protected $signature = 'test:college-flow';
    protected $description = 'Test complet du flux Collège (Classe -> Note -> Bulletin) - Architecture Zero Erreur';

    public function handle()
    {
        try {
            $this->info("=== DÉBUT DU TEST D'INTÉGRATION COLLÈGE ===");

            // 0. RESET COMPLET (school_college)
            $this->warn("Reset de la base school_college...");
            $this->call('migrate:fresh', [
                '--path' => 'database/migrations/college',
                '--database' => 'school_college',
                '--force' => true
            ]);

            // 1. SETUP ENVIRONNEMENT
            $year = SchoolYear::firstOrCreate(
                ['name' => '2024-2025'],
                ['start_date' => '2024-09-01', 'end_date' => '2025-06-30', 'is_current' => true]
            );

            // 2. CRÉATION CLASSE (6ème A)
            $classe = ClassCollege::create([
                'school_year_id' => $year->id,
                'niveau' => '6eme',
                'nom' => 'A',
                'seuil_minimum' => 30,
                'seuil_maximum' => 50,
                'effectif_actuel' => 0,
                'is_active' => true
            ]);
            $this->info("Classe 6ème A créée (ID: {$classe->id})");

            // 3. CRÉATION MATIÈRE (Français, Coeff 4 en 6ème)
            $francais = SubjectCollege::create([
                'code' => 'FRA-TEST',
                'nom' => 'Français',
                'coefficient_6eme' => 4,
                'coefficient_5eme' => 4,
                'coefficient_4eme' => 4,
                'coefficient_3eme' => 4,
                'is_active' => true
            ]);
            $this->info("Matière Français créée (Coeff 6ème: 4)");

            // 4. INSCRIPTION ÉLÈVE
            $eleve = StudentCollege::create([
                'matricule' => 'COL-TEST-' . rand(1000, 9999),
                'nom' => 'OUEDRAOGO',
                'prenoms' => 'Pierre',
                'date_naissance' => '2012-01-01',
                'lieu_naissance' => 'Bobo-Dioulasso',
                'sexe' => 'M',
                'is_active' => true
            ]);
            $this->info("Élève Pierre créé. ({$eleve->matricule})");

            // 5. NOTES
            // Devoir (Coeff 1) : 14/20
            GradeCollege::create([
                'school_year_id' => $year->id,
                'class_id' => $classe->id,
                'student_id' => $eleve->id,
                'subject_id' => $francais->id,
                'trimestre' => 1,
                'type_evaluation' => 'DS',
                'note_sur_20' => 14,
                'note_obtenue' => 14,
                'coefficient' => 1,
                'date_evaluation' => now(),
                'is_published' => true
            ]);

            // Compo (Coeff 2) : 16/20
            GradeCollege::create([
                'school_year_id' => $year->id,
                'class_id' => $classe->id,
                'student_id' => $eleve->id,
                'subject_id' => $francais->id,
                'trimestre' => 1,
                'type_evaluation' => 'Comp',
                'note_sur_20' => 16,
                'note_obtenue' => 16,
                'coefficient' => 2,
                'date_evaluation' => now(),
                'is_published' => true
            ]);
            $this->info("Notes ajoutées : 14 (Devoir) et 16 (Compo)");

            // 6. BULLETIN
            // Moyenne Matière = ((14*1) + (16*2)) / 3 = 46/3 = 15.333...
            // Points = 15.33 * 4 (Coeff mat) = 61.32 (Si arrondi moyen matière avant)
            // OU Calcul direct : (Somme(Notes*CoeffEval) / Somme(CoeffEval)) * CoeffMatiere
            // Le calculateur burkina.grading fait: MoyenneMatiere Arrondie à 2 décimales.
            // 46/3 = 15.33. Points = 15.33 * 4 = 61.32.
            // Moyenne Générale = TotalPoints / TotalCoeffs = 61.32 / 4 = 15.33.

            $service = new ReportCardCollegeService();
            $bulletin = $service->generateForStudent($eleve->id, $classe->id, $year->id, 1);

            $this->info("Moyenne Générale : {$bulletin->moyenne_generale}");

            if ($bulletin->moyenne_generale == 15.33) {
                $this->info("✅ SUCCÈS : Calcul exact (15.33 attendu)");
                file_put_contents('success_college.log', "SUCCES: Moyenne={$bulletin->moyenne_generale}");
            } else {
                $this->error("❌ ÉCHEC : Calcul incorrect (Reçu {$bulletin->moyenne_generale})");
                file_put_contents('success_college.log', "ECHEC: Moyenne={$bulletin->moyenne_generale}");
            }
        } catch (\Throwable $e) {
            $this->error("FATAL: " . $e->getMessage());
            file_put_contents('error_college.log', "ERREUR: " . $e->getMessage());
        }
    }
}
