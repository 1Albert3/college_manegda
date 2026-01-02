<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classId = 'a0b940aa-4026-4ca7-aee1-45c446044b8b'; // 2nde A

echo "Reverting enrollments for 2nde A to 'en_attente'...\n";

// Find validated enrollments for this class
$enrollments = \App\Models\Lycee\EnrollmentLycee::where('class_id', $classId)
    ->where('statut', 'validee')
    ->get();

if ($enrollments->isEmpty()) {
    echo "No validated enrollments found to revert.\n";
}

foreach ($enrollments as $e) {
    $e->statut = 'en_attente';
    $e->save();
    echo "Reverted enrollment for student ID: {$e->student_id}\n";
}

// Update class effectif
$class = \App\Models\Lycee\ClassLycee::find($classId);
if ($class) {
    $class->recalculateEffectif();
    echo "Recalculated class effectif: {$class->effectif_actuel}\n";
}
