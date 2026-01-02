<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = \App\Models\User::select('id', 'email', 'role', 'first_name', 'last_name')->get()->toArray();
print_r($users);
