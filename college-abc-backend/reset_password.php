<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Reset password for direction user
$newPassword = Hash::make('password123');
DB::connection('school_core')->table('users')
    ->where('email', 'direction@wend-manegda.bf')
    ->update(['password' => $newPassword]);

echo "Password reset for direction@wend-manegda.bf\n";
echo "New password: password123\n";

// Verify
$user = DB::connection('school_core')->table('users')->where('email', 'direction@wend-manegda.bf')->first();
echo "Verification: " . (Hash::check('password123', $user->password) ? 'OK' : 'FAILED') . "\n";
