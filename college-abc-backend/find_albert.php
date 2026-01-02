<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classId = 'a0b940aa-4026-4ca7-aee1-45c446044b8b'; // 2nde A
echo "Students in 2nde A ($classId):\n";

$enrollments = \App\Models\Lycee\EnrollmentLycee::where('class_id', $classId)->get();
foreach ($enrollments as $e) {
    if ($e->student) {
        echo "- ID: {$e->student->id} | Name: {$e->student->nom} {$e->student->prenoms} | Status: {$e->statut}\n";
    }
}

echo "\nSearching for 'Albert' in all Lycee students:\n";
$alberts = \App\Models\Lycee\StudentLycee::where('nom', 'like', '%Albert%')->orWhere('prenoms', 'like', '%Albert%')->get();
foreach ($alberts as $s) {
    echo "- ID: {$s->id} | Name: {$s->nom} {$s->prenoms}\n";
}
