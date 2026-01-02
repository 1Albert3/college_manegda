<?php

namespace App\Console\Commands;

use App\Models\SchoolYear;
use App\Models\MP\ClassMP;
use App\Models\MP\GradeMP;
use App\Models\MP\ReportCardMP;
use App\Models\MP\StudentMP;
use App\Models\MP\SubjectMP;
use App\Services\MP\ReportCardMPService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestMPFlow extends Command
{
    protected $signature = 'test:mp-flow';
    protected $description = 'Test complet du flux Maternelle/Primaire (Reset DB -> Bulletin)';

    public function handle()
    {
        $this->info("=== DÉBUT DU TEST D'INTÉGRATION MP (Maternelle/Primaire) ===");

        try {
            // 1. Reset Data (Truncate instead of migrate:fresh to avoid schema toggle issues)
            $this->info("Nettoyage des données MP...");
            Schema::connection('school_mp')->disableForeignKeyConstraints();

            // Ordre dépendant des FK
            GradeMP::truncate();
            ReportCardMP::truncate();
            StudentMP::truncate();
            ClassMP::truncate();
            SubjectMP::truncate();

            Schema::connection('school_mp')->enableForeignKeyConstraints();

            // 2. Année scolaire (id constant pour tests)
            $yearId = 'a0af89bc-4c33-44b7-aec0-9f35db05f0a7';

            // 3. Création Classe (CM2)
            $classe = ClassMP::create([
                'nom' => 'CM2 A',
                'niveau' => 'CM2',
                'school_year_id' => $yearId,
                'capacity' => 50
            ]);
            $this->info("Classe {$classe->niveau} {$classe->nom} créée (ID: {$classe->id})");

            // 4. Création Matières (CM2 a des coeff spécifiques)
            $maths = SubjectMP::create([
                'code' => 'MAT',
                'nom' => 'Mathématiques',
                'categorie' => 'sciences',
                'coefficient_cm1_cm2' => 4, // Coeff CM2
                'coefficient_ce2' => 4,
                'coefficient_cp_ce1' => 2,
                'is_active' => true
            ]);

            $francais = SubjectMP::create([
                'code' => 'FRA',
                'nom' => 'Français', // Dictée, rédaction, lecture...
                'categorie' => 'communication',
                'coefficient_cm1_cm2' => 5,
                'is_active' => true
            ]);

            $this->info("Matières Math (Coeff CM2: {$maths->coefficient_cm1_cm2}) et Français créées");

            // 5. Création Élève
            $eleve = StudentMP::create([
                'matricule' => 'MP-TEST-' . rand(1000, 9999),
                'prenoms' => 'Awa',
                'nom' => 'SANKARA',
                'date_naissance' => '2015-05-15',
                'lieu_naissance' => 'Ouaga',
                'sexe' => 'F',
                'nationalite' => 'Burkinabè',
                'statut_inscription' => 'nouveau'
            ]);
            $this->info("Élève {$eleve->prenoms} créé. ({$eleve->matricule})");

            // 6. Ajout de Notes

            // Note en Math (IO / 10 -> 8/10)
            // 8/10 -> 16/20
            GradeMP::create([
                'school_year_id' => $yearId,
                'class_id' => $classe->id,
                'student_id' => $eleve->id,
                'subject_id' => $maths->id,
                'trimestre' => '1',
                'type_evaluation' => 'IO',
                'note_sur' => 10,
                'note_obtenue' => 8,
                'date_evaluation' => now(),
                'is_published' => true,
                'recorded_by' => 'admin-test'
            ]);

            // Note en Math (Compo / 100 -> 75/100)
            // 75/100 -> 15/20
            GradeMP::create([
                'school_year_id' => $yearId,
                'class_id' => $classe->id,
                'student_id' => $eleve->id,
                'subject_id' => $maths->id,
                'trimestre' => '1',
                'type_evaluation' => 'CP',
                'note_sur' => 100,
                'note_obtenue' => 75,
                'date_evaluation' => now(),
                'is_published' => true,
                'recorded_by' => 'admin-test'
            ]);

            // Moyenne Math : (16 + 15) / 2 = 15.5
            // Points Math : 15.5 * 4 = 62

            // Note en Français (Devoir / 20 -> 14/20)
            GradeMP::create([
                'school_year_id' => $yearId,
                'class_id' => $classe->id,
                'student_id' => $eleve->id,
                'subject_id' => $francais->id,
                'trimestre' => '1',
                'type_evaluation' => 'DV',
                'note_sur' => 20,
                'note_obtenue' => 14,
                'date_evaluation' => now(),
                'is_published' => true,
                'recorded_by' => 'admin-test'
            ]);

            // Moyenne Français : 14
            // Points Français : 14 * 5 = 70

            $this->info("Notes ajoutées : Math(8/10, 75/100) et Français(14/20)");

            // 7. Génération Bulletin
            $service = new ReportCardMPService();
            $bulletin = $service->generateForStudent($eleve->id, $classe->id, $yearId, '1');

            if (!$bulletin) {
                $this->error("❌ ÉCHEC : Bulletin non généré (null)");
                return 1;
            }

            // Calcul attendu :
            // Total Pts = 62 (Math) + 70 (Fra) = 132
            // Total Coeff = 4 + 5 = 9
            // Moyenne Générale = 132 / 9 = 14.666... -> 14.67

            $this->info("Moyenne Générale : {$bulletin->moyenne_generale}");

            // On accepte 14.66 ou 14.67
            if (abs($bulletin->moyenne_generale - 14.67) < 0.02) {
                $this->info("✅ SUCCÈS : Calcul exact ({$bulletin->moyenne_generale})");
            } else {
                $this->error("❌ ÉCHEC : Calcul incorrect (Reçu {$bulletin->moyenne_generale}, Attendu ~14.67)");

                // Debug details
                // $this->line(json_encode($bulletin->data_matieres, JSON_PRETTY_PRINT));
                file_put_contents('error_mp_last.log', json_encode($bulletin->toArray(), JSON_PRETTY_PRINT));
                return 1;
            }

            // Test UpdateClassStats
            $service->updateClassStats($classe->id, $yearId, '1');
            $bulletin->refresh();
            $this->info("Rang élève : {$bulletin->rang}/{$bulletin->effectif_classe}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur critique : " . $e->getMessage());
            $this->error($e->getTraceAsString());
            file_put_contents('error_mp_critical.log', $e->__toString());
            return 1;
        }
    }
}
