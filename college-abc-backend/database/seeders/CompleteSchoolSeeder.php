<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Seeder Complet pour le Système Scolaire
 * 
 * Seed les 4 bases de données avec des données de démonstration
 */
class CompleteSchoolSeeder extends Seeder
{
    private string $currentSchoolYearId;
    private string $parentUserId;

    public function run(): void
    {
        $this->command->info('🏫 Début du seeding complet...');

        // 1. Base Core - Données centrales
        $this->seedCoreDatabase();

        // 2. Base MP - Maternelle/Primaire
        $this->seedMPDatabase();

        // 3. Base Collège
        $this->seedCollegeDatabase();

        // 4. Base Lycée
        $this->seedLyceeDatabase();

        // 5. Finance
        $this->seedFinance();

        $this->command->info('✅ Seeding terminé avec succès!');
    }

    /**
     * ===== BASE CORE (school_core) =====
     */
    private function seedCoreDatabase(): void
    {
        $this->command->info('📦 Seeding school_core...');

        // Année scolaire
        $this->currentSchoolYearId = Str::uuid()->toString();
        DB::table('school_years')->updateOrInsert(
            ['name' => '2024-2025'],
            [
                'id' => $this->currentSchoolYearId,
                'name' => '2024-2025',
                'start_date' => '2024-09-01',
                'end_date' => '2025-07-15',
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Rôles
        // Rôles
        $rolesData = [
            ['name' => 'direction', 'display_name' => 'Direction', 'description' => 'Personnel de direction'],
            ['name' => 'secretariat', 'display_name' => 'Secrétariat', 'description' => 'Personnel du secrétariat'],
            ['name' => 'comptabilite', 'display_name' => 'Comptabilité', 'description' => 'Personnel comptable'],
            ['name' => 'enseignant', 'display_name' => 'Enseignant', 'description' => 'Professeurs et instituteurs'],
            ['name' => 'parent', 'display_name' => 'Parent', 'description' => 'Parents d\'élèves'],
            ['name' => 'eleve', 'display_name' => 'Élève', 'description' => 'Élèves inscrits'],
        ];

        foreach ($rolesData as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']], // Check by unique name
                [
                    'id' => Str::uuid()->toString(), // This might change ID on update if not careful, but updateOrInsert won't insert if exists. 
                    // Actually updateOrInsert UPDATEs if exists. We want to avoid overwriting ID.
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            // Fix: updateOrInsert with 'id' in the values array will try to update the ID if the record exists, which might be fine or problematic. 
            // Better to check existence first or just use insertOrIgnore with explicit IDs if we wanted to enforce them.
            // But since I'm wiping the DB, insert is fine, or simple logic.
        }

        // Let's rewrite the loop to be safe for re-seeding (though we wipe typically)
        foreach ($rolesData as $role) {
            if (!DB::table('roles')->where('name', $role['name'])->exists()) {
                DB::table('roles')->insert(array_merge($role, [
                    'id' => Str::uuid()->toString(),
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Utilisateurs de test
        $users = [
            [
                'id' => Str::uuid()->toString(),
                'first_name' => 'Directeur',
                'last_name' => 'OUEDRAOGO',
                'email' => 'direction@wend-manegda.bf',
                'password' => Hash::make('password123'),
                'role' => 'direction',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'first_name' => 'Secrétaire',
                'last_name' => 'TAMBOUR',
                'email' => 'secretariat@wend-manegda.bf',
                'password' => Hash::make('password123'),
                'role' => 'secretariat',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'first_name' => 'Comptable',
                'last_name' => 'YAMEOGO',
                'email' => 'comptabilite@wend-manegda.bf',
                'password' => Hash::make('password123'),
                'role' => 'comptabilite',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'first_name' => 'Martin',
                'last_name' => 'KABORE',
                'email' => 'prof.kabore@wend-manegda.bf',
                'password' => Hash::make('password123'),
                'role' => 'enseignant',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'first_name' => 'Parent',
                'last_name' => 'SAWADOGO',
                'email' => 'parent.sawadogo@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'parent',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }

        // Capture parent ID
        $this->parentUserId = DB::table('users')->where('email', 'parent.sawadogo@gmail.com')->value('id');



        $this->command->info('  ✓ Core database seeded');
    }

    /**
     * ===== BASE MATERNELLE/PRIMAIRE (school_mp) =====
     */
    private function seedMPDatabase(): void
    {
        $this->command->info('📦 Seeding school_mp...');

        // Classes MP
        $classes = [
            ['id' => Str::uuid(), 'niveau' => 'PS', 'nom' => 'A', 'cycle' => 'maternelle', 'capacite' => 25, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'MS', 'nom' => 'A', 'cycle' => 'maternelle', 'capacite' => 25, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'GS', 'nom' => 'A', 'cycle' => 'maternelle', 'capacite' => 30, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'CP1', 'nom' => 'A', 'cycle' => 'primaire', 'capacite' => 40, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'CP2', 'nom' => 'A', 'cycle' => 'primaire', 'capacite' => 40, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'CE1', 'nom' => 'A', 'cycle' => 'primaire', 'capacite' => 45, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'CE2', 'nom' => 'A', 'cycle' => 'primaire', 'capacite' => 45, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'CM1', 'nom' => 'A', 'cycle' => 'primaire', 'capacite' => 45, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'CM2', 'nom' => 'A', 'cycle' => 'primaire', 'capacite' => 45, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'CM2', 'nom' => 'B', 'cycle' => 'primaire', 'capacite' => 45, 'active' => true],
        ];

        foreach ($classes as $class) {
            $fullName = $class['niveau'] . ' ' . $class['nom'];
            DB::connection('school_mp')->table('classes_mp')->updateOrInsert(
                ['school_year_id' => $this->currentSchoolYearId, 'nom' => $fullName],
                [
                    'id' => $class['id'],
                    'school_year_id' => $this->currentSchoolYearId,
                    'niveau' => $class['niveau'],
                    'nom' => $fullName,
                    'seuil_maximum' => $class['capacite'],
                    'is_active' => $class['active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Matières primaire
        $subjects = [
            ['id' => Str::uuid(), 'code' => 'FRA', 'nom' => 'Français', 'coefficient' => 4, 'heures_semaine' => 8],
            ['id' => Str::uuid(), 'code' => 'MAT', 'nom' => 'Mathématiques', 'coefficient' => 4, 'heures_semaine' => 6],
            ['id' => Str::uuid(), 'code' => 'HG', 'nom' => 'Histoire-Géographie', 'coefficient' => 2, 'heures_semaine' => 2],
            ['id' => Str::uuid(), 'code' => 'SVT', 'nom' => 'Sciences', 'coefficient' => 2, 'heures_semaine' => 2],
            ['id' => Str::uuid(), 'code' => 'ANG', 'nom' => 'Anglais', 'coefficient' => 1, 'heures_semaine' => 2],
            ['id' => Str::uuid(), 'code' => 'EPS', 'nom' => 'Sport', 'coefficient' => 1, 'heures_semaine' => 2],
            ['id' => Str::uuid(), 'code' => 'DESSIN', 'nom' => 'Dessin', 'coefficient' => 1, 'heures_semaine' => 1],
        ];

        foreach ($subjects as $subject) {
            DB::connection('school_mp')->table('subjects_mp')->updateOrInsert(
                ['code' => $subject['code']],
                [
                    'id' => $subject['id'],
                    'code' => $subject['code'],
                    'nom' => $subject['nom'],
                    'coefficient' => $subject['coefficient'],
                    // Mappage des nouveaux champs
                    'categorie' => 'enseignement_general',
                    'coefficient_maternelle' => 0,
                    'coefficient_cp_ce1' => $subject['coefficient'], // Simplification pour le seed
                    'coefficient_ce2' => $subject['coefficient'],
                    'coefficient_cm1_cm2' => $subject['coefficient'],
                    'volume_horaire_hebdo' => $subject['heures_semaine'],
                    'type_evaluation' => 'notes',
                    'is_active' => true,
                    'is_obligatoire' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Enseignants
        $teachers = [
            ['id' => Str::uuid(), 'matricule' => 'ENS-MP-001', 'nom' => 'ZONGO', 'prenom' => 'Mariam', 'sexe' => 'F', 'telephone' => '70112233', 'email' => 'zongo.m@wend-manegda.bf', 'specialite' => 'Maternelle', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-MP-002', 'nom' => 'OUEDRAOGO', 'prenom' => 'Amadou', 'sexe' => 'M', 'telephone' => '70223344', 'email' => 'ouedraogo.a@wend-manegda.bf', 'specialite' => 'CM2', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-MP-003', 'nom' => 'KABORE', 'prenom' => 'Salimata', 'sexe' => 'F', 'telephone' => '70334455', 'email' => 'kabore.s@wend-manegda.bf', 'specialite' => 'CP/CE', 'statut' => 'titulaire'],
        ];

        foreach ($teachers as $teacher) {
            // Create user first
            $userId = Str::uuid()->toString();
            // Check if user exists (by email) to avoid duplicates if re-seeding without wipe
            $existingUser = DB::table('users')->where('email', $teacher['email'])->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                DB::table('users')->insert([
                    'id' => $userId,
                    'first_name' => $teacher['prenom'],
                    'last_name' => $teacher['nom'],
                    'email' => $teacher['email'],
                    'password' => Hash::make('password123'),
                    'role' => 'enseignant',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::connection('school_mp')->table('teachers_mp')->updateOrInsert(
                ['matricule' => $teacher['matricule']],
                [
                    'id' => $teacher['id'],
                    'user_id' => $userId,
                    'matricule' => $teacher['matricule'],
                    'specialites' => json_encode([$teacher['specialite']]),
                    'date_embauche' => '2020-01-01', // Default
                    'type_contrat' => 'permanent',
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Élèves de démonstration
        $cm2ClassId = DB::connection('school_mp')->table('classes_mp')->where('nom', 'CM2 A')->value('id');

        $students = [
            ['nom' => 'SAWADOGO', 'prenoms' => 'Abdoul Razak', 'sexe' => 'M', 'date_naissance' => '2013-03-15', 'lieu_naissance' => 'Ouagadougou'],
            ['nom' => 'COMPAORE', 'prenoms' => 'Fatimata', 'sexe' => 'F', 'date_naissance' => '2013-07-22', 'lieu_naissance' => 'Bobo-Dioulasso'],
            ['nom' => 'TRAORE', 'prenoms' => 'Moussa', 'sexe' => 'M', 'date_naissance' => '2013-01-10', 'lieu_naissance' => 'Ouagadougou'],
            ['nom' => 'NIKIEMA', 'prenoms' => 'Amina', 'sexe' => 'F', 'date_naissance' => '2013-09-05', 'lieu_naissance' => 'Koudougou'],
            ['nom' => 'TIENDREBEOGO', 'prenoms' => 'Ibrahim', 'sexe' => 'M', 'date_naissance' => '2013-04-30', 'lieu_naissance' => 'Ouagadougou'],
        ];

        foreach ($students as $index => $student) {
            $matricule = 'ELV-MP-' . Carbon::now()->format('Y') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            // Check if student exists
            $existingStudent = DB::connection('school_mp')->table('students_mp')
                ->where('matricule', $matricule)->first();

            if ($existingStudent) {
                $studentId = $existingStudent->id;
            } else {
                $studentId = Str::uuid()->toString();
                DB::connection('school_mp')->table('students_mp')->insert(
                    array_merge($student, [
                        'id' => $studentId,
                        'matricule' => $matricule,
                        'nationalite' => 'Burkinabè',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            // Inscription - check if exists
            $existingEnrollment = DB::connection('school_mp')->table('enrollments_mp')
                ->where('student_id', $studentId)
                ->where('school_year_id', $this->currentSchoolYearId)
                ->exists();

            if (!$existingEnrollment) {
                DB::connection('school_mp')->table('enrollments_mp')->insert([
                    'id' => Str::uuid()->toString(),
                    'student_id' => $studentId,
                    'class_id' => $cm2ClassId,
                    'school_year_id' => $this->currentSchoolYearId,
                    'regime' => 'externe',
                    'date_inscription' => now(),
                    'statut' => 'validee',
                    'frais_scolarite' => 75000,
                    'frais_inscription' => 10000,
                    'total_a_payer' => 85000,
                    'montant_final' => 85000,
                    'mode_paiement' => 'tranches_3',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // LINK GUARDIAN - check if exists
            if ($this->parentUserId) {
                $existingGuardian = DB::connection('school_mp')->table('guardians_mp')
                    ->where('student_id', $studentId)
                    ->where('type', 'pere')
                    ->exists();

                if (!$existingGuardian) {
                    DB::connection('school_mp')->table('guardians_mp')->insert([
                        'id' => Str::uuid()->toString(),
                        'student_id' => $studentId,
                        'type' => 'pere',
                        'nom_complet' => 'Parent SAWADOGO',
                        'telephone_1' => '70000000',
                        'email' => 'parent.sawadogo@gmail.com',
                        'adresse_physique' => 'Ouagadougou',
                        'user_id' => $this->parentUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('  ✓ MP database seeded');
    }

    /**
     * ===== BASE COLLÈGE (school_college) =====
     */
    private function seedCollegeDatabase(): void
    {
        $this->command->info('📦 Seeding school_college...');

        // Classes Collège
        $classes = [
            ['id' => Str::uuid(), 'niveau' => '6eme', 'nom' => 'A', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '6eme', 'nom' => 'B', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '5eme', 'nom' => 'A', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '5eme', 'nom' => 'B', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '4eme', 'nom' => 'A', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '4eme', 'nom' => 'B', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '3eme', 'nom' => 'A', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '3eme', 'nom' => 'B', 'capacite' => 50, 'active' => true],
        ];

        foreach ($classes as $class) {
            $fullName = $class['niveau'] . ' ' . $class['nom'];
            DB::connection('school_college')->table('classes_college')->updateOrInsert(
                ['school_year_id' => $this->currentSchoolYearId, 'nom' => $fullName],
                [
                    'id' => $class['id'],
                    'school_year_id' => $this->currentSchoolYearId,
                    'niveau' => $class['niveau'],
                    'nom' => $fullName,
                    'seuil_maximum' => $class['capacite'],
                    'is_active' => $class['active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Matières Collège avec coefficients
        $subjects = [
            ['id' => Str::uuid(), 'code' => 'FRA', 'nom' => 'Français', 'coefficient' => 5, 'heures_semaine' => 5],
            ['id' => Str::uuid(), 'code' => 'MAT', 'nom' => 'Mathématiques', 'coefficient' => 5, 'heures_semaine' => 5],
            ['id' => Str::uuid(), 'code' => 'ANG', 'nom' => 'Anglais', 'coefficient' => 3, 'heures_semaine' => 4],
            ['id' => Str::uuid(), 'code' => 'ALL', 'nom' => 'Allemand', 'coefficient' => 2, 'heures_semaine' => 3],
            ['id' => Str::uuid(), 'code' => 'HG', 'nom' => 'Histoire-Géographie', 'coefficient' => 3, 'heures_semaine' => 4],
            ['id' => Str::uuid(), 'code' => 'SVT', 'nom' => 'Sciences de la Vie et de la Terre', 'coefficient' => 3, 'heures_semaine' => 3],
            ['id' => Str::uuid(), 'code' => 'PC', 'nom' => 'Physique-Chimie', 'coefficient' => 3, 'heures_semaine' => 3],
            ['id' => Str::uuid(), 'code' => 'EPS', 'nom' => 'Éducation Physique et Sportive', 'coefficient' => 2, 'heures_semaine' => 2],
            ['id' => Str::uuid(), 'code' => 'ECM', 'nom' => 'Éducation Civique et Morale', 'coefficient' => 2, 'heures_semaine' => 1],
            ['id' => Str::uuid(), 'code' => 'PHILO', 'nom' => 'Philosophie', 'coefficient' => 2, 'heures_semaine' => 2],
        ];

        foreach ($subjects as $subject) {
            DB::connection('school_college')->table('subjects_college')->updateOrInsert(
                ['code' => $subject['code']],
                [
                    'id' => $subject['id'],
                    'code' => $subject['code'],
                    'nom' => $subject['nom'],
                    'coefficient_6eme' => $subject['coefficient'],
                    'coefficient_5eme' => $subject['coefficient'],
                    'coefficient_4eme' => $subject['coefficient'],
                    'coefficient_3eme' => $subject['coefficient'],
                    'volume_horaire_hebdo' => $subject['heures_semaine'],
                    'is_active' => true,
                    'is_obligatoire' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Enseignants Collège
        $teachers = [
            ['id' => Str::uuid(), 'matricule' => 'ENS-COL-001', 'nom' => 'DIALLO', 'prenom' => 'Mamadou', 'sexe' => 'M', 'telephone' => '70445566', 'specialite' => 'Mathématiques', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-COL-002', 'nom' => 'BAMBARA', 'prenom' => 'Aissata', 'sexe' => 'F', 'telephone' => '70556677', 'specialite' => 'Français', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-COL-003', 'nom' => 'SOME', 'prenom' => 'Pierre', 'sexe' => 'M', 'telephone' => '70667788', 'specialite' => 'Physique-Chimie', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-COL-004', 'nom' => 'BARRY', 'prenom' => 'Ousmane', 'sexe' => 'M', 'telephone' => '70778899', 'specialite' => 'Anglais', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-COL-005', 'nom' => 'SANOGO', 'prenom' => 'Mariame', 'sexe' => 'F', 'telephone' => '70889900', 'specialite' => 'SVT', 'statut' => 'titulaire'],
        ];

        foreach ($teachers as $teacher) {
            // Create user first
            $userId = Str::uuid()->toString();
            // Check if user exists (by email to generate deterministic UUIDs or reuse) - here just reuse logic
            // Ideally we'd have emails in the seed data for all teachers
            $email = strtolower($teacher['nom'] . '.' . $teacher['prenom'] . '@college-abc.com');

            $existingUser = DB::table('users')->where('email', $email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                DB::table('users')->insert([
                    'id' => $userId,
                    'first_name' => $teacher['prenom'],
                    'last_name' => $teacher['nom'],
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'role' => 'enseignant',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::connection('school_college')->table('teachers_college')->updateOrInsert(
                ['matricule' => $teacher['matricule']],
                [
                    'id' => $teacher['id'],
                    'user_id' => $userId,
                    'matricule' => $teacher['matricule'],
                    'specialites' => json_encode([$teacher['specialite']]),
                    'date_embauche' => '2020-01-01',
                    'type_contrat' => 'permanent',
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Élèves de démonstration (3ème pour BEPC)
        $troisiemeClassId = DB::connection('school_college')->table('classes_college')->where('nom', '3eme A')->value('id');

        $students = [
            ['nom' => 'OUATTARA', 'prenoms' => 'Seydou', 'sexe' => 'M', 'date_naissance' => '2009-02-18', 'lieu_naissance' => 'Ouagadougou'],
            ['nom' => 'COULIBALY', 'prenoms' => 'Fatoumata', 'sexe' => 'F', 'date_naissance' => '2009-08-25', 'lieu_naissance' => 'Bobo-Dioulasso'],
            ['nom' => 'ZONGO', 'prenoms' => 'Abdoul Karim', 'sexe' => 'M', 'date_naissance' => '2009-05-12', 'lieu_naissance' => 'Ouagadougou'],
            ['nom' => 'PARE', 'prenoms' => 'Aminata', 'sexe' => 'F', 'date_naissance' => '2009-11-30', 'lieu_naissance' => 'Fada N\'Gourma'],
            ['nom' => 'ILBOUDO', 'prenoms' => 'Yacouba', 'sexe' => 'M', 'date_naissance' => '2009-06-08', 'lieu_naissance' => 'Ouagadougou'],
        ];

        foreach ($students as $index => $student) {
            $matricule = 'ELV-COL-' . Carbon::now()->format('Y') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            // Check if student exists
            $existingStudent = DB::connection('school_college')->table('students_college')
                ->where('matricule', $matricule)->first();

            if ($existingStudent) {
                $studentId = $existingStudent->id;
            } else {
                $studentId = Str::uuid()->toString();
                DB::connection('school_college')->table('students_college')->insert(
                    array_merge($student, [
                        'id' => $studentId,
                        'matricule' => $matricule,
                        'nationalite' => 'Burkinabè',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            // Inscription - check if exists
            $existingEnrollment = DB::connection('school_college')->table('enrollments_college')
                ->where('student_id', $studentId)
                ->where('school_year_id', $this->currentSchoolYearId)
                ->exists();

            if (!$existingEnrollment) {
                DB::connection('school_college')->table('enrollments_college')->insert([
                    'id' => Str::uuid()->toString(),
                    'student_id' => $studentId,
                    'class_id' => $troisiemeClassId,
                    'school_year_id' => $this->currentSchoolYearId,
                    'regime' => 'externe',
                    'date_inscription' => now(),
                    'statut' => 'validee',
                    'frais_scolarite' => 125000,
                    'frais_inscription' => 10000,
                    'total_a_payer' => 135000,
                    'montant_final' => 135000,
                    'mode_paiement' => 'tranches_3',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // LINK GUARDIAN - check if exists
            if ($this->parentUserId) {
                $existingGuardian = DB::connection('school_college')->table('guardians_college')
                    ->where('student_id', $studentId)
                    ->where('type', 'pere')
                    ->exists();

                if (!$existingGuardian) {
                    DB::connection('school_college')->table('guardians_college')->insert([
                        'id' => Str::uuid()->toString(),
                        'student_id' => $studentId,
                        'type' => 'pere',
                        'nom_complet' => 'Parent SAWADOGO',
                        'telephone_1' => '70000000',
                        'email' => 'parent.sawadogo@gmail.com',
                        'adresse_physique' => 'Ouagadougou',
                        'user_id' => $this->parentUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // SEED SCHEDULES for 3eme A
        $subjects_list = DB::connection('school_college')->table('subjects_college')->get();
        $teachers_list = DB::connection('school_college')->table('teachers_college')->get();

        if ($troisiemeClassId && $subjects_list->count() > 0 && $teachers_list->count() > 0) {
            $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
            $times = [
                ['07:30', '08:30'],
                ['08:30', '09:30'],
                ['10:00', '11:00'],
                ['11:00', '12:00'],
            ];

            foreach ($days as $dayIndex => $dayName) {
                foreach ($times as $timeIndex => $time) {
                    $subject = $subjects_list->random();
                    $teacher = $teachers_list->random();

                    DB::connection('school_college')->table('schedules')->insert([
                        'id' => Str::uuid()->toString(),
                        'class_id' => $troisiemeClassId,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'school_year_id' => $this->currentSchoolYearId,
                        'day_number' => $dayIndex + 1,
                        'day_name' => $dayName,
                        'start_time' => $time[0],
                        'end_time' => $time[1],
                        'room' => 'Salle 3A',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('  ✓ College database seeded');
    }

    /**
     * ===== BASE LYCÉE (school_lycee) =====
     */
    private function seedLyceeDatabase(): void
    {
        $this->command->info('📦 Seeding school_lycee...');

        // Classes Lycée avec séries
        $classes = [
            ['id' => Str::uuid(), 'niveau' => '2nde', 'nom' => 'A', 'serie' => null, 'capacite' => 55, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '2nde', 'nom' => 'B', 'serie' => null, 'capacite' => 55, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '1ere', 'nom' => 'A1', 'serie' => 'A', 'capacite' => 45, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '1ere', 'nom' => 'D1', 'serie' => 'D', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => '1ere', 'nom' => 'C1', 'serie' => 'C', 'capacite' => 40, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'Tle', 'nom' => 'A1', 'serie' => 'A', 'capacite' => 45, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'Tle', 'nom' => 'D1', 'serie' => 'D', 'capacite' => 50, 'active' => true],
            ['id' => Str::uuid(), 'niveau' => 'Tle', 'nom' => 'C1', 'serie' => 'C', 'capacite' => 40, 'active' => true],
        ];

        foreach ($classes as $class) {
            $fullName = $class['niveau'] . ' ' . $class['nom'];
            if ($class['serie']) {
                $fullName .= ' ' . $class['serie'];
            }

            DB::connection('school_lycee')->table('classes_lycee')->updateOrInsert(
                ['school_year_id' => $this->currentSchoolYearId, 'nom' => $fullName],
                [
                    'id' => $class['id'],
                    'school_year_id' => $this->currentSchoolYearId,
                    'niveau' => $class['niveau'],
                    'nom' => $fullName,
                    'serie' => $class['serie'],
                    'seuil_maximum' => $class['capacite'],
                    'is_active' => $class['active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Matières Lycée avec coefficients par série
        $subjects = [
            ['id' => Str::uuid(), 'code' => 'FRA', 'nom' => 'Français', 'coefficient_a' => 5, 'coefficient_c' => 2, 'coefficient_d' => 3, 'heures_semaine' => 4],
            ['id' => Str::uuid(), 'code' => 'PHILO', 'nom' => 'Philosophie', 'coefficient_a' => 6, 'coefficient_c' => 2, 'coefficient_d' => 2, 'heures_semaine' => 4],
            ['id' => Str::uuid(), 'code' => 'MAT', 'nom' => 'Mathématiques', 'coefficient_a' => 2, 'coefficient_c' => 7, 'coefficient_d' => 5, 'heures_semaine' => 6],
            ['id' => Str::uuid(), 'code' => 'PC', 'nom' => 'Physique-Chimie', 'coefficient_a' => 1, 'coefficient_c' => 6, 'coefficient_d' => 4, 'heures_semaine' => 5],
            ['id' => Str::uuid(), 'code' => 'SVT', 'nom' => 'Sciences de la Vie et de la Terre', 'coefficient_a' => 1, 'coefficient_c' => 2, 'coefficient_d' => 6, 'heures_semaine' => 4],
            ['id' => Str::uuid(), 'code' => 'ANG', 'nom' => 'Anglais', 'coefficient_a' => 3, 'coefficient_c' => 2, 'coefficient_d' => 2, 'heures_semaine' => 3],
            ['id' => Str::uuid(), 'code' => 'HG', 'nom' => 'Histoire-Géographie', 'coefficient_a' => 4, 'coefficient_c' => 2, 'coefficient_d' => 2, 'heures_semaine' => 3],
            ['id' => Str::uuid(), 'code' => 'EPS', 'nom' => 'Éducation Physique', 'coefficient_a' => 2, 'coefficient_c' => 2, 'coefficient_d' => 2, 'heures_semaine' => 2],
        ];

        foreach ($subjects as $subject) {
            DB::connection('school_lycee')->table('subjects_lycee')->updateOrInsert(
                ['code' => $subject['code']],
                [
                    'id' => $subject['id'],
                    'code' => $subject['code'],
                    'nom' => $subject['nom'],
                    'coefficient_2nde' => $subject['coefficient_d'] ?? $subject['coefficient_a'],
                    'coefficient_1ere_A' => $subject['coefficient_a'],
                    'coefficient_1ere_C' => $subject['coefficient_c'],
                    'coefficient_1ere_D' => $subject['coefficient_d'],
                    'coefficient_tle_A' => $subject['coefficient_a'],
                    'coefficient_tle_C' => $subject['coefficient_c'],
                    'coefficient_tle_D' => $subject['coefficient_d'],
                    'volume_horaire_hebdo' => $subject['heures_semaine'],
                    'is_active' => true,
                    'is_obligatoire' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Enseignants Lycée
        $teachers = [
            ['id' => Str::uuid(), 'matricule' => 'ENS-LYC-001', 'nom' => 'DRABO', 'prenom' => 'Aristide', 'sexe' => 'M', 'telephone' => '71001122', 'specialite' => 'Philosophie', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-LYC-002', 'nom' => 'ZOUNDI', 'prenom' => 'Justine', 'sexe' => 'F', 'telephone' => '71112233', 'specialite' => 'Mathématiques', 'statut' => 'titulaire'],
            ['id' => Str::uuid(), 'matricule' => 'ENS-LYC-003', 'nom' => 'KAFANDO', 'prenom' => 'Hamidou', 'sexe' => 'M', 'telephone' => '71223344', 'specialite' => 'Physique-Chimie', 'statut' => 'titulaire'],
        ];

        foreach ($teachers as $teacher) {
            // Create user first
            $userId = Str::uuid()->toString();
            $email = strtolower($teacher['nom'] . '.' . $teacher['prenom'] . '@college-abc.com');

            $existingUser = DB::table('users')->where('email', $email)->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                DB::table('users')->insert([
                    'id' => $userId,
                    'first_name' => $teacher['prenom'],
                    'last_name' => $teacher['nom'],
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'role' => 'enseignant',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::connection('school_lycee')->table('teachers_lycee')->updateOrInsert(
                ['matricule' => $teacher['matricule']],
                [
                    'id' => $teacher['id'],
                    'user_id' => $userId,
                    'matricule' => $teacher['matricule'],
                    'specialites' => json_encode([$teacher['specialite']]),
                    'date_embauche' => '2020-01-01',
                    'type_contrat' => 'permanent',
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Élèves de démonstration (Terminale pour BAC)
        $terminaleClassId = DB::connection('school_lycee')->table('classes_lycee')->where('nom', 'Tle D1 D')->value('id');

        $students = [
            ['nom' => 'KONATE', 'prenoms' => 'Boubacar', 'sexe' => 'M', 'date_naissance' => '2006-01-20', 'lieu_naissance' => 'Ouagadougou'],
            ['nom' => 'DAKUYO', 'prenoms' => 'Clarisse', 'sexe' => 'F', 'date_naissance' => '2006-04-15', 'lieu_naissance' => 'Banfora'],
            ['nom' => 'SANOU', 'prenoms' => 'Arouna', 'sexe' => 'M', 'date_naissance' => '2006-07-22', 'lieu_naissance' => 'Bobo-Dioulasso'],
        ];

        foreach ($students as $index => $student) {
            $matricule = 'ELV-LYC-' . Carbon::now()->format('Y') . '-' . str_pad($index + 4, 4, '0', STR_PAD_LEFT);

            // Check if student exists
            $existingStudent = DB::connection('school_lycee')->table('students_lycee')
                ->where('matricule', $matricule)->first();

            if ($existingStudent) {
                $studentId = $existingStudent->id;
            } else {
                $studentId = Str::uuid()->toString();
                DB::connection('school_lycee')->table('students_lycee')->insert(
                    array_merge($student, [
                        'id' => $studentId,
                        'matricule' => $matricule,
                        'nationalite' => 'Burkinabè',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            // Inscription - check if exists
            $existingEnrollment = DB::connection('school_lycee')->table('enrollments_lycee')
                ->where('student_id', $studentId)
                ->where('school_year_id', $this->currentSchoolYearId)
                ->exists();

            if (!$existingEnrollment) {
                DB::connection('school_lycee')->table('enrollments_lycee')->insert([
                    'id' => Str::uuid()->toString(),
                    'student_id' => $studentId,
                    'class_id' => $terminaleClassId,
                    'school_year_id' => $this->currentSchoolYearId,
                    'regime' => 'externe',
                    'date_inscription' => now(),
                    'statut' => 'validee',
                    'frais_scolarite' => 150000,
                    'frais_inscription' => 15000,
                    'total_a_payer' => 165000,
                    'montant_final' => 165000,
                    'mode_paiement' => 'tranches_3',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // LINK GUARDIAN - check if exists
            if ($this->parentUserId) {
                $existingGuardian = DB::connection('school_lycee')->table('guardians_lycee')
                    ->where('student_id', $studentId)
                    ->where('type', 'pere')
                    ->exists();

                if (!$existingGuardian) {
                    DB::connection('school_lycee')->table('guardians_lycee')->insert([
                        'id' => Str::uuid()->toString(),
                        'student_id' => $studentId,
                        'type' => 'pere',
                        'nom_complet' => 'Parent SAWADOGO',
                        'telephone_1' => '70000000',
                        'email' => 'parent.sawadogo@gmail.com',
                        'adresse_physique' => 'Ouagadougou',
                        'user_id' => $this->parentUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('  ✓ Lycee database seeded');
    }

    /**
     * ===== FINANCE (school_core) =====
     */
    private function seedFinance(): void
    {
        $this->command->info('📦 Seeding finance data...');

        $fees = [
            // Maternelle
            ['cycle' => 'maternelle', 'niveau' => 'PS', 'inscription' => 10000, 'scolarite' => 50000, 'total' => 60000],
            ['cycle' => 'maternelle', 'niveau' => 'MS', 'inscription' => 10000, 'scolarite' => 50000, 'total' => 60000],
            ['cycle' => 'maternelle', 'niveau' => 'GS', 'inscription' => 10000, 'scolarite' => 55000, 'total' => 65000],
            // Primaire
            ['cycle' => 'primaire', 'niveau' => 'CP1', 'inscription' => 10000, 'scolarite' => 65000, 'total' => 75000],
            ['cycle' => 'primaire', 'niveau' => 'CP2', 'inscription' => 10000, 'scolarite' => 65000, 'total' => 75000],
            ['cycle' => 'primaire', 'niveau' => 'CE1', 'inscription' => 10000, 'scolarite' => 70000, 'total' => 80000],
            ['cycle' => 'primaire', 'niveau' => 'CE2', 'inscription' => 10000, 'scolarite' => 70000, 'total' => 80000],
            ['cycle' => 'primaire', 'niveau' => 'CM1', 'inscription' => 10000, 'scolarite' => 75000, 'total' => 85000],
            ['cycle' => 'primaire', 'niveau' => 'CM2', 'inscription' => 10000, 'scolarite' => 75000, 'total' => 85000],
            // Collège
            ['cycle' => 'college', 'niveau' => '6eme', 'inscription' => 10000, 'scolarite' => 100000, 'total' => 110000],
            ['cycle' => 'college', 'niveau' => '5eme', 'inscription' => 10000, 'scolarite' => 100000, 'total' => 110000],
            ['cycle' => 'college', 'niveau' => '4eme', 'inscription' => 10000, 'scolarite' => 110000, 'total' => 120000],
            ['cycle' => 'college', 'niveau' => '3eme', 'inscription' => 10000, 'scolarite' => 125000, 'total' => 135000],
            // Lycée
            ['cycle' => 'lycee', 'niveau' => '2nde', 'inscription' => 15000, 'scolarite' => 135000, 'total' => 150000],
            ['cycle' => 'lycee', 'niveau' => '1ere', 'serie' => 'A', 'inscription' => 15000, 'scolarite' => 145000, 'total' => 160000],
            ['cycle' => 'lycee', 'niveau' => '1ere', 'serie' => 'D', 'inscription' => 15000, 'scolarite' => 150000, 'total' => 165000],
            ['cycle' => 'lycee', 'niveau' => '1ere', 'serie' => 'C', 'inscription' => 15000, 'scolarite' => 155000, 'total' => 170000],
            ['cycle' => 'lycee', 'niveau' => 'Tle', 'serie' => 'A', 'inscription' => 15000, 'scolarite' => 155000, 'total' => 170000],
            ['cycle' => 'lycee', 'niveau' => 'Tle', 'serie' => 'D', 'inscription' => 15000, 'scolarite' => 160000, 'total' => 175000],
            ['cycle' => 'lycee', 'niveau' => 'Tle', 'serie' => 'C', 'inscription' => 15000, 'scolarite' => 165000, 'total' => 180000],
        ];

        foreach ($fees as $fee) {
            DB::table('fee_structures')->updateOrInsert(
                [
                    'school_year_id' => $this->currentSchoolYearId,
                    'cycle' => $fee['cycle'],
                    'niveau' => $fee['niveau'],
                    'serie' => $fee['serie'] ?? null,
                ],
                array_merge($fee, [
                    'id' => Str::uuid()->toString(),
                    'school_year_id' => $this->currentSchoolYearId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('  ✓ Finance data seeded');
    }
}
