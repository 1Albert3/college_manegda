<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Hash;

// Reset passwords according to cahier des charges
$usersToUpdate = [
    'direction@wend-manegda.bf' => 'directeur',
    'secretariat@wend-manegda.bf' => 'secretariat',
    'comptabilite@wend-manegda.bf' => 'comptabilite',
];

// Update passwords
foreach ($usersToUpdate as $email => $password) {
    $user = \App\Models\User::where('email', $email)->first();
    if ($user) {
        $user->password = Hash::make($password);
        $user->save();
        echo "Updated password for: $email\n";
    } else {
        echo "User not found: $email\n";
    }
}

echo "\nNow checking teachers...\n";

// Also update all teachers with default password 'enseignant'
$teachers = \App\Models\User::where('role', 'enseignant')->get();
foreach ($teachers as $teacher) {
    $teacher->password = Hash::make('enseignant');
    $teacher->save();
    echo "Updated teacher: " . $teacher->email . "\n";
}

echo "\nDone!\n";
