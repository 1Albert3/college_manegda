<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Get direction user
$user = User::where('email', 'direction@wend-manegda.bf')->first();

if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User: " . $user->email . "\n";

// Create a token
$token = $user->createToken('test-token')->plainTextToken;
echo "Token: " . $token . "\n";

// Test the class creation
$client = new \GuzzleHttp\Client();

try {
    $response = $client->post('http://localhost:8000/api/v1/college/classes', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'nom' => 'Test A',
            'niveau' => '6eme',
            'school_year_id' => '6a96779a-6505-42f1-9a70-9df9d2062b53',
            'seuil_minimum' => 15,
            'seuil_maximum' => 40,
        ],
    ]);

    echo "Response: " . $response->getBody() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse') && $e->getResponse()) {
        echo "Response body: " . $e->getResponse()->getBody() . "\n";
    }
}
