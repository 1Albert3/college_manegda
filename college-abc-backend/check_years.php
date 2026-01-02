<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SchoolYear;

echo "=== School Years in Database ===\n";
$years = SchoolYear::all();
foreach ($years as $y) {
    $current = $y->is_current ? '[CURRENT]' : '';
    echo "- {$y->name} ({$y->start_date} to {$y->end_date}) $current\n";
}
echo "\nTotal: " . $years->count() . " years\n";
