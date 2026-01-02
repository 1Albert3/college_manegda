<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\User;
use Modules\Student\Entities\Student;

class LinkParentSeeder extends Seeder
{
    public function run(): void
    {
        $parentEmail = 'parent@test.com';
        $parent = User::where('email', $parentEmail)->first();

        if (!$parent) {
            $this->command->error("Parent user {$parentEmail} not found. Run UserSeeder first.");
            return;
        }

        // Get 3 random students who don't have a user_id yet
        $students = Student::inRandomOrder()->limit(3)->get();

        if ($students->isEmpty()) {
            $this->command->warn("No students found to link.");
            return;
        }

        foreach ($students as $student) {
            // Method 1: Direct link
            $student->user_id = $parent->id;
            $student->save();
            
            // Method 2: Pivot link (if supported by relations)
            // $student->parents()->syncWithoutDetaching([$parent->id => ['relationship' => 'Father', 'is_primary' => true]]);

            $this->command->info("Linked student {$student->matricule} ({$student->first_name}) to parent {$parentEmail}");
        }
    }
}
