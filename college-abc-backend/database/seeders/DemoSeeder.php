<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\Level;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\Subject;
use Modules\Student\Entities\Enrollment;
use Modules\Grade\Entities\Evaluation;
use Modules\Grade\Entities\Grade;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DemoSeeder extends Seeder
{
    private $defaultPassword = 'Password123!';

    public function run(): void
    {
        $this->command->info('🗑️  Nettoyage de la base de données...');
        $this->clearDatabase();

        $this->command->info('🔐 Création des rôles et permissions...');
        $this->createRolesAndPermissions();

        $this->command->info('📅 Création de l\'année académique...');
        $academicYear = $this->createAcademicYear();

        $this->command->info('🏫 Création des niveaux et classes...');
        $classes = $this->createLevelsAndClasses($academicYear);

        $this->command->info('📚 Création des matières...');
        $subjects = $this->createSubjects();

        $this->command->info('👤 Création des acteurs...');
        $actors = $this->createActors();

        $this->command->info('👨‍🎓 Création des élèves et inscriptions...');
        $students = $this->createStudentsAndEnrollments($actors, $classes, $academicYear);
        $randomStudents = $this->createRandomStudents($classes, $academicYear, 50);
        $students = array_merge($students, $randomStudents);

        $this->command->info('📝 Création des évaluations et notes...');
        $this->createEvaluationsAndGrades($actors['teacher'], $classes, $subjects, $students, $academicYear);

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('                    🎓 DONNÉES DE DÉMO CRÉÉES                   ');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->displayCredentials($actors, $students);
    }

    private function clearDatabase(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear all tables
        Grade::truncate();
        Evaluation::truncate();
        Enrollment::truncate();
        DB::table('parent_student')->truncate();
        Student::truncate();
        ClassRoom::truncate();
        Level::truncate();
        DB::table('cycles')->truncate();
        Subject::truncate();
        AcademicYear::truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        Role::truncate();
        Permission::truncate();
        User::truncate();
        DB::table('personal_access_tokens')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createRolesAndPermissions(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-users',
            'create-users',
            'update-users',
            'delete-users',
            'view-students',
            'create-students',
            'update-students',
            'delete-students',
            'view-teachers',
            'create-teachers',
            'update-teachers',
            'delete-teachers',
            'view-academic',
            'manage-academic',
            'view-attendance',
            'mark-attendance',
            'manage-attendance',
            'view-grades',
            'enter-grades',
            'manage-grades',
            'view-finance',
            'create-payments',
            'manage-payments',
            'send-sms',
            'send-emails',
            'view-communications',
            'view-reports',
            'generate-reports',
            'export-reports',
            'manage-settings',
            'view-activity-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Super Admin - all permissions
        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions(Permission::all());

        // Director
        $director = Role::create(['name' => 'director', 'guard_name' => 'sanctum']);
        $director->syncPermissions([
            'view-users',
            'create-users',
            'update-users',
            'view-students',
            'create-students',
            'update-students',
            'delete-students',
            'view-teachers',
            'create-teachers',
            'update-teachers',
            'view-academic',
            'manage-academic',
            'view-grades',
            'manage-grades',
            'view-attendance',
            'manage-attendance',
            'view-finance',
            'manage-payments',
            'view-reports',
            'generate-reports',
            'export-reports',
        ]);

        // Teacher
        $teacher = Role::create(['name' => 'teacher', 'guard_name' => 'sanctum']);
        $teacher->syncPermissions([
            'view-students',
            'update-students',
            'view-academic',
            'manage-academic',
            'view-grades',
            'enter-grades',
            'view-attendance',
            'mark-attendance',
            'view-reports',
        ]);

        // Secretary
        $secretary = Role::create(['name' => 'secretary', 'guard_name' => 'sanctum']);
        $secretary->syncPermissions([
            'view-users',
            'view-students',
            'create-students',
            'update-students',
            'view-teachers',
            'view-academic',
            'view-attendance',
            'mark-attendance',
            'view-communications',
            'send-sms',
            'send-emails',
        ]);

        // Parent
        Role::create(['name' => 'parent', 'guard_name' => 'sanctum']);
    }

    private function createAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
            'is_current' => true,
        ]);
    }

    private function createLevelsAndClasses(AcademicYear $academicYear): array
    {
        $classes = [];

        // Créer les cycles d'abord
        $cycleCollege = \Modules\Academic\Entities\Cycle::create([
            'name' => 'Collège',
            'slug' => 'college',
            'description' => 'Cycle du collège (6ème à 3ème)',
            'order' => 1,
        ]);

        $cycleLycee = \Modules\Academic\Entities\Cycle::create([
            'name' => 'Lycée',
            'slug' => 'lycee',
            'description' => 'Cycle du lycée (2nde à Terminale)',
            'order' => 2,
        ]);

        $levels = [
            ['name' => '6ème', 'order' => 1, 'cycle_id' => $cycleCollege->id],
            ['name' => '5ème', 'order' => 2, 'cycle_id' => $cycleCollege->id],
            ['name' => '4ème', 'order' => 3, 'cycle_id' => $cycleCollege->id],
            ['name' => '3ème', 'order' => 4, 'cycle_id' => $cycleCollege->id],
            ['name' => '2nde', 'order' => 5, 'cycle_id' => $cycleLycee->id],
            ['name' => '1ère', 'order' => 6, 'cycle_id' => $cycleLycee->id],
            ['name' => 'Tle', 'order' => 7, 'cycle_id' => $cycleLycee->id],
        ];

        foreach ($levels as $levelData) {
            $level = Level::create([
                'name' => $levelData['name'],
                'code' => str_replace(['ème', 'nde', 'ère'], '', $levelData['name']),
                'order' => $levelData['order'],
                'cycle_id' => $levelData['cycle_id'],
            ]);

            $classroom = ClassRoom::create([
                'name' => $levelData['name'] . ' A',
                'room_number' => $level->code . 'A',
                'level_id' => $level->id,
                'academic_year_id' => $academicYear->id,
                'capacity' => 40,
            ]);

            $classes[$levelData['name']] = $classroom;
        }

        return $classes;
    }

    private function createSubjects(): array
    {
        $subjectsData = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 4],
            ['name' => 'Français', 'code' => 'FRA', 'coefficient' => 4],
            ['name' => 'Anglais', 'code' => 'ANG', 'coefficient' => 2],
            ['name' => 'Histoire-Géographie', 'code' => 'HG', 'coefficient' => 2],
            ['name' => 'Sciences Physiques', 'code' => 'PC', 'coefficient' => 3],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'coefficient' => 2],
            ['name' => 'Éducation Physique', 'code' => 'EPS', 'coefficient' => 1],
        ];

        $subjects = [];
        foreach ($subjectsData as $data) {
            $subjects[] = Subject::create($data);
        }

        return $subjects;
    }

    private function createActors(): array
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'Administrateur Système',
            'email' => 'admin@manegda.bf',
            'password' => Hash::make($this->defaultPassword),
            'role' => 'super_admin',
        ]);
        $superAdmin->assignRole(Role::findByName('super_admin', 'sanctum'));

        // Directeur
        $director = User::create([
            'name' => 'Dr. Jean-Baptiste OUEDRAOGO',
            'email' => 'directeur@manegda.bf',
            'password' => Hash::make($this->defaultPassword),
            'role' => 'director',
        ]);
        $director->assignRole(Role::findByName('director', 'sanctum'));

        // Enseignant
        $teacher = User::create([
            'name' => 'Prof. Marie SAWADOGO',
            'email' => 'enseignant@manegda.bf',
            'password' => Hash::make($this->defaultPassword),
            'role' => 'teacher',
        ]);
        $teacher->assignRole(Role::findByName('teacher', 'sanctum'));

        // Secrétaire
        $secretary = User::create([
            'name' => 'Mme. Clarisse TRAORE',
            'email' => 'secretaire@manegda.bf',
            'password' => Hash::make($this->defaultPassword),
            'role' => 'secretary',
        ]);
        $secretary->assignRole(Role::findByName('secretary', 'sanctum'));

        // Parent 1 (5 enfants)
        $parent1 = User::create([
            'name' => 'M. Abdoulaye KONE',
            'email' => 'parent1@manegda.bf',
            'password' => Hash::make($this->defaultPassword),
            'role' => 'parent',
        ]);
        $parent1->assignRole(Role::findByName('parent', 'sanctum'));

        // Parent 2 (2 enfants)
        $parent2 = User::create([
            'name' => 'Mme. Fatou DIALLO',
            'email' => 'parent2@manegda.bf',
            'password' => Hash::make($this->defaultPassword),
            'role' => 'parent',
        ]);
        $parent2->assignRole(Role::findByName('parent', 'sanctum'));

        return [
            'super_admin' => $superAdmin,
            'director' => $director,
            'secretary' => $secretary,
            'teacher' => $teacher,
            'parent1' => $parent1,
            'parent2' => $parent2,
        ];
    }

    private function createStudentsAndEnrollments(array $actors, array $classes, AcademicYear $academicYear): array
    {
        $students = [];

        // 5 enfants pour Parent 1
        $parent1Children = [
            ['first_name' => 'Amadou', 'last_name' => 'KONE', 'gender' => 'M', 'class' => '6ème'],
            ['first_name' => 'Aissata', 'last_name' => 'KONE', 'gender' => 'F', 'class' => '5ème'],
            ['first_name' => 'Ibrahim', 'last_name' => 'KONE', 'gender' => 'M', 'class' => '4ème'],
            ['first_name' => 'Mariam', 'last_name' => 'KONE', 'gender' => 'F', 'class' => '3ème'],
            ['first_name' => 'Ousmane', 'last_name' => 'KONE', 'gender' => 'M', 'class' => '2nde'],
        ];

        $matriculeCounter = 1;
        foreach ($parent1Children as $childData) {
            $student = Student::create([
                'matricule' => sprintf('25-KON-%04d', $matriculeCounter++),
                'first_name' => $childData['first_name'],
                'last_name' => $childData['last_name'],
                'gender' => $childData['gender'],
                'date_of_birth' => now()->subYears(rand(10, 18)),
                'place_of_birth' => 'Ouagadougou',
                'address' => 'Karpala, Secteur 51',
                'status' => 'active',
            ]);

            // Lier au parent via table pivot
            $student->parents()->attach($actors['parent1']->id, [
                'relationship' => 'père',
                'is_primary' => true,
            ]);

            // Inscription dans la classe
            Enrollment::create([
                'student_id' => $student->id,
                'class_room_id' => $classes[$childData['class']]->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now(),
                'status' => 'active',
            ]);

            $students[] = ['student' => $student, 'class' => $childData['class']];
        }

        // 2 enfants pour Parent 2
        $parent2Children = [
            ['first_name' => 'Aminata', 'last_name' => 'DIALLO', 'gender' => 'F', 'class' => '1ère'],
            ['first_name' => 'Moussa', 'last_name' => 'DIALLO', 'gender' => 'M', 'class' => 'Tle'],
        ];

        $matriculeCounter = 1;
        foreach ($parent2Children as $childData) {
            $student = Student::create([
                'matricule' => sprintf('25-DIA-%04d', $matriculeCounter++),
                'first_name' => $childData['first_name'],
                'last_name' => $childData['last_name'],
                'gender' => $childData['gender'],
                'date_of_birth' => now()->subYears(rand(15, 18)),
                'place_of_birth' => 'Bobo-Dioulasso',
                'address' => 'Secteur 22',
                'status' => 'active',
            ]);

            // Lier au parent
            $student->parents()->attach($actors['parent2']->id, [
                'relationship' => 'mère',
                'is_primary' => true,
            ]);

            // Inscription
            Enrollment::create([
                'student_id' => $student->id,
                'class_room_id' => $classes[$childData['class']]->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now(),
                'status' => 'active',
            ]);

            $students[] = ['student' => $student, 'class' => $childData['class']];
        }

        return $students;
    }

    private function createRandomStudents(array $classes, AcademicYear $academicYear, int $count = 50): array
    {
        $faker = \Faker\Factory::create('fr_FR');
        $randomStudents = [];
        $classKeys = array_keys($classes);

        for ($i = 0; $i < $count; $i++) {
            $gender = $faker->randomElement(['M', 'F']);
            $firstName = $gender === 'M' ? $faker->firstNameMale : $faker->firstNameFemale;
            $lastName = $faker->lastName;
            $classKey = $faker->randomElement($classKeys);

            $student = Student::create([
                'matricule' => sprintf('25-RND-%04d', $i + 1),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(10, 18)),
                'place_of_birth' => $faker->city,
                'address' => $faker->address,
                'status' => 'active',
            ]);

            Enrollment::create([
                'student_id' => $student->id,
                'class_room_id' => $classes[$classKey]->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now(),
                'status' => 'active',
            ]);

            $randomStudents[] = ['student' => $student, 'class' => $classKey];
        }

        return $randomStudents;
    }

    private function createEvaluationsAndGrades(
        User $teacher,
        array $classes,
        array $subjects,
        array $students,
        AcademicYear $academicYear
    ): void {
        foreach ($classes as $className => $classroom) {
            foreach ($subjects as $subject) {
                // Créer une évaluation par matière et classe
                $evaluation = Evaluation::create([
                    'title' => 'Devoir 1 - ' . $subject->name,
                    'code' => strtoupper(mb_substr($className, 0, 2)) . $subject->code . '01',
                    'type' => 'test',
                    'coefficient' => $subject->coefficient,
                    'maximum_score' => 20,
                    'minimum_score' => 0,
                    'academic_year_id' => $academicYear->id,
                    'subject_id' => $subject->id,
                    'class_room_id' => $classroom->id,
                    'teacher_id' => $teacher->id,
                    'evaluation_date' => now()->subDays(rand(1, 30)),
                    'status' => 'completed',
                ]);

                // Créer les notes pour les élèves de cette classe
                foreach ($students as $studentData) {
                    if ($studentData['class'] === $className) {
                        Grade::create([
                            'student_id' => $studentData['student']->id,
                            'evaluation_id' => $evaluation->id,
                            'score' => rand(8, 20),
                            'coefficient' => $subject->coefficient,
                            'is_absent' => false,
                            'recorded_by' => $teacher->id,
                            'recorded_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    private function displayCredentials(array $actors, array $students): void
    {
        $this->command->info('┌───────────────────────────────────────────────────────────────┐');
        $this->command->info('│                    IDENTIFIANTS DE CONNEXION                  │');
        $this->command->info('├───────────────────────────────────────────────────────────────┤');
        $this->command->info('│ Mot de passe commun: ' . $this->defaultPassword . str_repeat(' ', 30) . '│');
        $this->command->info('├───────────────────────────────────────────────────────────────┤');
        $this->command->info('│                                                               │');
        $this->command->info('│ 👑 SUPER ADMIN                                                │');
        $this->command->info('│    Email: admin@manegda.bf                                │');
        $this->command->info('│    Accès: /admin/* (Tout le système)                          │');
        $this->command->info('│                                                               │');
        $this->command->info('│ 🎓 DIRECTEUR                                                  │');
        $this->command->info('│    Email: directeur@manegda.bf                            │');
        $this->command->info('│    Accès: /admin/* (Gestion complète)                         │');
        $this->command->info('│                                                               │');
        $this->command->info('│ 👨‍🏫 ENSEIGNANT                                                 │');
        $this->command->info('│    Email: enseignant@manegda.bf                           │');
        $this->command->info('│    Accès: /admin/* (Notes, Présences)                         │');
        $this->command->info('│                                                               │');
        $this->command->info('│ 👩‍💼 SECRÉTAIRE                                                │');
        $this->command->info('│    Email: secretaire@manegda.bf                           │');
        $this->command->info('│    Accès: /admin/* (Inscriptions, Paiements)                  │');
        $this->command->info('│                                                               │');
        $this->command->info('├───────────────────────────────────────────────────────────────┤');
        $this->command->info('│                                                               │');
        $this->command->info('│ 👨‍👩‍👧 PARENT 1 - M. Abdoulaye KONE (5 enfants)                   │');
        $this->command->info('│    Matricules pour se connecter:                              │');

        foreach ($students as $idx => $studentData) {
            if ($idx < 5) { // Parent 1's children
                $this->command->info('│      • ' . $studentData['student']->matricule . ' (' . $studentData['student']->first_name . ' - ' . $studentData['class'] . ')' . str_repeat(' ', 20) . '│');
            }
        }

        $this->command->info('│    Accès: /parents/dashboard                                  │');
        $this->command->info('│                                                               │');
        $this->command->info('│ 👨‍👩‍👧 PARENT 2 - Mme. Fatou DIALLO (2 enfants)                   │');
        $this->command->info('│    Matricules pour se connecter:                              │');

        foreach ($students as $idx => $studentData) {
            if ($idx >= 5) { // Parent 2's children
                $this->command->info('│      • ' . $studentData['student']->matricule . ' (' . $studentData['student']->first_name . ' - ' . $studentData['class'] . ')' . str_repeat(' ', 20) . '│');
            }
        }

        $this->command->info('│    Accès: /parents/dashboard                                  │');
        $this->command->info('│                                                               │');
        $this->command->info('└───────────────────────────────────────────────────────────────┘');
        $this->command->info('');
        $this->command->info('📊 Statistiques:');
        $this->command->info('   • 7 classes créées (6ème à Tle)');
        $this->command->info('   • 7 matières créées');
        $this->command->info('   • 7 élèves inscrits');
        $this->command->info('   • 49 évaluations créées (7 matières × 7 classes)');
        $this->command->info('   • Notes saisies pour chaque élève');
    }
}
