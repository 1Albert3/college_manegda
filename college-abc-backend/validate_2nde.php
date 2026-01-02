<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classId = 'a0b940aa-4026-4ca7-aee1-45c446044b8b'; // 2nde A

echo "Validating enrollments for 2nde A...\n";

$enrollments = \App\Models\Lycee\EnrollmentLycee::where('class_id', $classId)
    ->where('statut', 'en_attente')
    ->get();

foreach ($enrollments as $e) {
    $e->statut = 'validee';
    $e->save();
    echo "Validated enrollment for student ID: {$e->student_id}\n";
}

// Update class effectif
$class = \App\Models\Lycee\ClassLycee::find($classId);
if ($class) {
    $class->recalculateEffectif();
    echo "Recalculated class effectif: {$class->effectif_actuel}\n";
}
