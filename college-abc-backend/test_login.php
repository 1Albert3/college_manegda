<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$user = DB::connection('school_core')->table('users')->where('email', 'direction@wend-manegda.bf')->first();

if ($user) {
    echo "User found: " . $user->email . "\n";
    echo "Role: " . $user->role . "\n";
    echo "is_active: " . ($user->is_active ? 'yes' : 'no') . "\n";
    echo "Password hash: " . substr($user->password, 0, 30) . "...\n";
    echo "Password check (password123): " . (Hash::check('password123', $user->password) ? 'OK' : 'FAILED') . "\n";
} else {
    echo "User not found!\n";
}
