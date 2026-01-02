<?php

require __DIR__ . '/college-abc-backend/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

// Increase timeout for slow local server
$client = new Client([
    'base_uri' => 'http://localhost:8000/api/v1/',
    'http_errors' => false,
    'timeout'  => 10.0,
]);

echo "--- STARTING SECREATARIAT UNIVERSAL FLOW TEST ---\n";

try {
    // 1. LOGIN
    echo "\n1. Logging in as Secretariat...\n";
    $response = $client->post('auth/login', [
        'json' => [
            'identifier' => 'secretariat@wend-manegda.bf',
            'password' => 'Secretariat@2024'
        ]
    ]);

    $body = json_decode($response->getBody(), true);
    if ($response->getStatusCode() !== 200 || !($body['success'] ?? false)) {
        echo "Login Failed!\n";
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Body: " . $response->getBody() . "\n";
        exit(1);
    }

    $token = $body['access_token'];
    echo "Login Successful.\n";

    // 2. CREATE STUDENT (Universal Route)
    echo "\n2. Creating Student via Universal Route (POST /students)...\n";

    $randomId = rand(1000, 9999);
    $studentData = [
        'first_name' => 'Jean_' . $randomId,
        'last_name' => 'TEST_AUTO',
        'birth_date' => '2012-01-01',
        'gender' => 'M',
        'parent_name' => 'Papa Test',
        'parent_phone' => '70000000',
        'class_name' => '6ème A', // Should trigger College creation
        'status' => 'active'
    ];

    $respCreate = $client->post('students', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ],
        'json' => $studentData
    ]);

    echo "Status Code: " . $respCreate->getStatusCode() . "\n";
    $createBody = json_decode($respCreate->getBody(), true);

    if ($respCreate->getStatusCode() === 201) {
        echo "Student Created Successfully!\n";
        print_r($createBody);

        $studentId = $createBody['student']['id'] ?? null;
        if (!$studentId) {
            echo "ERROR: No Student ID returned.\n";
            exit(1);
        }

        // 3. SHOW STUDENT (Universal Route)
        echo "\n3. Fetching Student Details (GET /students/$studentId)...\n";
        $respShow = $client->get("students/$studentId", [
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ]);

        if ($respShow->getStatusCode() === 200) {
            echo "Student Details Fetched OK.\n";
            $showBody = json_decode($respShow->getBody(), true);
            echo "Name: " . ($showBody['nom'] ?? '') . " " . ($showBody['prenoms'] ?? '') . "\n";

            // Check enrollment status
            $currentStatus = $showBody['enrollments'][0]['statut'] ?? 'unknown';
            echo "Current Enrollment Status: $currentStatus\n";
        } else {
            echo "Failed to fetch student details: " . $respShow->getStatusCode() . "\n";
        }

        // 4. UPDATE STATUS (Universal Route)
        echo "\n4. Updating Student Status to 'rejected' (PUT /students/$studentId)...\n";
        $respUpdate = $client->put("students/$studentId", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => ['status' => 'rejected']
        ]);

        if ($respUpdate->getStatusCode() === 200) {
            echo "Update Successful.\n";
            $updateBody = json_decode($respUpdate->getBody(), true);
            // Verify
            $respShow2 = $client->get("students/$studentId", [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ]);
            $showBody2 = json_decode($respShow2->getBody(), true);
            echo "New Enrollment Status: " . ($showBody2['enrollments'][0]['statut'] ?? 'unknown') . "\n";
        } else {
            echo "Update Failed: " . $respUpdate->getStatusCode() . "\n";
            echo $respUpdate->getBody() . "\n";
        }
    } else {
        echo "Student Creation Failed.\n";
        echo "Body: " . $respCreate->getBody() . "\n";
        // Check for common errors
        if ($respCreate->getStatusCode() === 422) {
            echo "Validation Error Details:\n";
            print_r($createBody);
        }
        exit(1);
    }
} catch (\Exception $e) {
    echo "CRITICAL EXCEPTION: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse') && $e->getResponse()) {
        echo "Response Body: " . $e->getResponse()->getBody() . "\n";
    }
}
