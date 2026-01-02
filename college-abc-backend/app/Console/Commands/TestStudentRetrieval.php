<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestStudentRetrieval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Testing Student Retrieval...");

        // 1. Get a college class
        $class = \App\Models\College\ClassCollege::first();
        if (!$class) {
            $this->error("No College Class found!");
            return;
        }
        $this->info("Class found: {$class->nom} (ID: {$class->id})");

        // 2. Get Current School Year
        $schoolYear = \App\Models\SchoolYear::current();
        if (!$schoolYear) {
            $this->error("No Active School Year found!");
            return;
        }
        $this->info("School Year: {$schoolYear->name} (ID: {$schoolYear->id})");

        // 3. Test Query
        $students = \App\Models\College\StudentCollege::whereHas('enrollments', function ($q) use ($class, $schoolYear) {
            $q->where('class_id', $class->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('statut', 'validee');
        })->get();

        $this->info("Found " . $students->count() . " students via query.");

        // 4. Debug if count is 0
        if ($students->count() === 0) {
            $this->warn("Checking raw enrollments...");
            $enrollments = \App\Models\College\EnrollmentCollege::where('class_id', $class->id)->get();
            $this->info("Total Enrollments for class: " . $enrollments->count());

            foreach ($enrollments as $enr) {
                $this->line("- StudentID: {$enr->student_id} | Year: {$enr->school_year_id} | Statut: {$enr->statut}");
            }
        } else {
            foreach ($students as $s) {
                $this->line("- {$s->nom} {$s->prenoms} ({$s->matricule})");
            }
        }
    }
}
