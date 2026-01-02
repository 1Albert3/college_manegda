<?php

namespace Modules\Student\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Student\Entities\Student;
use Modules\Student\Entities\Guardian;
use Modules\Student\Entities\Enrollment;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\ClassRoom;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎓 Seeding Student Module...');

        $faker = Faker::create('fr_FR');
        $academicYear = AcademicYear::where('is_current', true)->first();
        $classRooms = ClassRoom::where('is_active', true)->get();

        if (!$academicYear || $classRooms->isEmpty()) {
            $this->command->warn('⚠️  Academic data missing. Run AcademicSeeder first!');
            return;
        }

        $studentsCreated = 0;

        foreach ($classRooms as $classRoom) {
            // 15-30 élèves par classe
            $studentCount = rand(15, min(30, $classRoom->capacity ?? 30));

            for ($i = 0; $i < $studentCount; $i++) {
                $gender = $faker->randomElement(['M', 'F']);
                
                $student = Student::create([
                    'matricule' => $this->generateMatricule(),
                    'first_name' => $faker->firstName($gender === 'M' ? 'male' : 'female'),
                    'last_name' => $faker->lastName,
                    'date_of_birth' => $faker->dateTimeBetween('-18 years', '-6 years'),
                    'place_of_birth' => $faker->randomElement(['Ouagadougou', 'Bobo-Dioulasso', 'Koudougou', 'Ouahigouya']),
                    'gender' => $gender,
                    'email' => rand(0, 1) ? $faker->email : null,
                    'phone' => rand(0, 1) ? $faker->phoneNumber : null,
                    'address' => $faker->address,
                    'blood_group' => $faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', null]),
                    'nationality' => 'Burkinabé',
                    'religion' => $faker->randomElement(['Islam', 'Christianisme', 'Animiste', null]),
                    'status' => 'active',
                ]);

                // Add Guardians (2-3 par élève)
                $guardianCount = rand(2, 3);
                $relationships = ['father', 'mother', 'guardian', 'uncle', 'aunt'];
                
                for ($j = 0; $j < $guardianCount; $j++) {
                    Guardian::create([
                        'student_id' => $student->id,
                        'relationship' => $relationships[$j] ?? 'other',
                        'first_name' => $faker->firstName,
                        'last_name' => $faker->lastName,
                        'phone' => $faker->phoneNumber,
                        'email' => rand(0, 1) ? $faker->email : null,
                        'profession' => $faker->jobTitle,
                        'address' => $faker->address,
                        'is_primary' => $j === 0, // Premier = principal
                        'can_pick_up' => true,
                    ]);
                }

                // Enroll in class
                Enrollment::create([
                    'student_id' => $student->id,
                    'class_room_id' => $classRoom->id,
                    'academic_year_id' => $academicYear->id,
                    'enrollment_date' => $academicYear->start_date,
                    'status' => 'ACTIVE',
                    'discount_percentage' => $faker->randomElement([0, 0, 0, 10, 25]),
                ]);

                $studentsCreated++;
            }
        }

        $this->command->info("✓ {$studentsCreated} students created with guardians and enrollments");
        $this->command->info('🎉 Student Module seeded successfully!');
    }

    protected function generateMatricule(): string
    {
        $year = date('Y');
        $count = Student::whereYear('created_at', $year)->count() + 1;
        return sprintf('STU%s%04d', $year, $count);
    }
}
