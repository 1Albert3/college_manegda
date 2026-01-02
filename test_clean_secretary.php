<?php

require __DIR__ . '/college-abc-backend/vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8000/api/v1/',
    'http_errors' => false,
    'timeout' => 30.0,
]);

echo "=== SECRETARIAT ENROLLMENT TEST ===\n\n";

// 1. LOGIN
echo "[1] Login...\n";
$response = $client->post('auth/login', [
    'json' => [
        'identifier' => 'secretariat@wend-manegda.bf',
        'password' => 'Secretariat@2024'
    ]
]);

$body = json_decode($response->getBody(), true);
if ($response->getStatusCode() !== 200) {
    die("Login Failed: " . $response->getBody() . "\n");
}
$token = $body['access_token'];
echo "    ✅ Login OK\n\n";

// 2. CREATE STUDENT
echo "[2] Creating Student...\n";
$randomId = rand(1000, 9999);
$studentData = [
    'first_name' => 'Jean_' . $randomId,
    'last_name' => 'TEST_AUTO',
    'birth_date' => '2012-01-01',
    'gender' => 'M',
    'parent_name' => 'Papa Test',
    'parent_phone' => '70000000',
    'class_name' => '6eme A',
    'status' => 'active'
];

$resp = $client->post('students', [
    'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
    'json' => $studentData
]);

echo "    Status: " . $resp->getStatusCode() . "\n";

if ($resp->getStatusCode() === 201) {
    $data = json_decode($resp->getBody(), true);
    $studentId = $data['student']['id'] ?? null;
    $studentMatricule = $data['student']['matricule'] ?? 'N/A';
    $cycle = $data['cycle'] ?? 'N/A';

    echo "    ✅ Student CREATED!\n";
    echo "    ID: $studentId\n";
    echo "    Matricule: $studentMatricule\n";
    echo "    Cycle: $cycle\n\n";

    // 3. FETCH STUDENT
    echo "[3] Fetching Student Details...\n";
    $resp = $client->get("students/$studentId", [
        'headers' => ['Authorization' => 'Bearer ' . $token]
    ]);

    if ($resp->getStatusCode() === 200) {
        $studentData = json_decode($resp->getBody(), true);
        $enrollmentStatus = $studentData['enrollments'][0]['statut'] ?? 'unknown';
        echo "    ✅ Student fetched\n";
        echo "    Name: " . ($studentData['nom'] ?? '') . " " . ($studentData['prenoms'] ?? '') . "\n";
        echo "    Enrollment Status: $enrollmentStatus\n\n";

        // 4. UPDATE STATUS TO REJECTED
        echo "[4] Updating Status to 'rejected'...\n";
        $resp = $client->put("students/$studentId", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => ['status' => 'rejected']
        ]);

        if ($resp->getStatusCode() === 200) {
            echo "    ✅ Update OK\n";

            // Verify
            $resp = $client->get("students/$studentId", [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ]);
            $verifyData = json_decode($resp->getBody(), true);
            $newStatus = $verifyData['enrollments'][0]['statut'] ?? 'unknown';
            echo "    New Status: $newStatus\n\n";
        } else {
            echo "    ❌ Update Failed: " . $resp->getStatusCode() . "\n";
        }
    } else {
        echo "    ❌ Fetch Failed: " . $resp->getStatusCode() . "\n";
    }
} else {
    echo "    ❌ Creation Failed\n";
    $errorData = json_decode($resp->getBody(), true);
    echo "    Error: " . ($errorData['message'] ?? 'Unknown') . "\n";
    if (isset($errorData['errors'])) {
        print_r($errorData['errors']);
    }
}

echo "\n=== TEST COMPLETE ===\n";
