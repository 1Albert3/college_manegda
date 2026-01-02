<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Lycee\GradeLycee;

$student = \App\Models\Lycee\StudentLycee::first();
$count = GradeLycee::where('student_id', $student->id)->count();

echo "Student: {$student->nom} {$student->prenoms}\n";
echo "Grades count for this student: $count\n";

if ($count > 0) {
    $grade = GradeLycee::where('student_id', $student->id)->first();
    echo "Last grade ID: {$grade->id}\n";
    echo "Recorded by: {$grade->recorded_by}\n";
    echo "Teacher ID: {$grade->teacher_id}\n";
}
