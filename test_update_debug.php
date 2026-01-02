<?php

require __DIR__ . '/college-abc-backend/vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8000/api/v1/',
    'http_errors' => false,
    'timeout' => 30.0,
]);

echo "=== QUICK STATUS UPDATE TEST ===\n\n";

// 1. LOGIN
$response = $client->post('auth/login', [
    'json' => [
        'identifier' => 'secretariat@wend-manegda.bf',
        'password' => 'Secretariat@2024'
    ]
]);

$body = json_decode($response->getBody(), true);
$token = $body['access_token'];
echo "Login OK\n";

// 2. GET STUDENT ID 
echo "\nFetching existing students...\n";
$resp = $client->get('dashboard/secretary/students', [
    'headers' => ['Authorization' => 'Bearer ' . $token]
]);
$students = json_decode($resp->getBody(), true);
$firstStudent = $students['data'][0] ?? $students[0] ?? null;

if (!$firstStudent) {
    die("No students found!\n");
}

$studentId = $firstStudent['id'];
echo "Found student: " . ($firstStudent['nom'] ?? $firstStudent['last_name'] ?? 'N/A') . " (ID: $studentId)\n";

// 3. TRY UPDATE
echo "\nUpdating status to 'rejected'...\n";
$resp = $client->put("students/$studentId", [
    'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
    'json' => ['status' => 'rejected']
]);

echo "Status Code: " . $resp->getStatusCode() . "\n";
echo "Response Body:\n";
echo $resp->getBody() . "\n";
