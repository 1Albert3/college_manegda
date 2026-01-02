<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classId = 'a0b940aa-4026-4ca7-aee1-45c446044b8b'; // 2nde A

echo "Checking Students Table:\n";
try {
    $allStudents = \App\Models\Lycee\StudentLycee::limit(5)->get();
    if ($allStudents->isEmpty()) {
        echo "No students found in students_lycee table.\n";
    } else {
        foreach ($allStudents as $s) {
            echo "Found Student: {$s->nom} {$s->prenoms} (ID: {$s->id})\n";
        }
    }
} catch (\Exception $e) {
    echo "Error querying students: " . $e->getMessage() . "\n";
}

echo "\nChecking Enrollments for 2nde A ($classId):\n";
try {
    $enrollments = \App\Models\Lycee\EnrollmentLycee::where('class_id', $classId)->get();
    if ($enrollments->isEmpty()) {
        echo "No enrollments found for this class.\n";
    } else {
        foreach ($enrollments as $e) {
            echo "Enrollment Status: {$e->statut}, Student ID: {$e->student_id}\n";
        }
    }
} catch (\Exception $e) {
    echo "Error querying enrollments: " . $e->getMessage() . "\n";
}
