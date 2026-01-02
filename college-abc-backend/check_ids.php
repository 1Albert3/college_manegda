<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "LYCEE SUBJECTS:\n";
foreach (\App\Models\Lycee\SubjectLycee::all() as $s) {
    echo "ID: {$s->id} | Name: {$s->nom} | Code: {$s->code}\n";
}

echo "\nLYCEE CLASSES:\n";
foreach (\App\Models\Lycee\ClassLycee::all() as $c) {
    echo "ID: {$c->id} | Name: {$c->nom} | Level: {$c->niveau}\n";
}
