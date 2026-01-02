<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

echo "Starting Login Authentication Test against Localhost...\n";

$client = new Client([
    'base_uri' => 'http://localhost:8000',
    'timeout'  => 5.0,
    'http_errors' => false // We want to handle errors manually
]);

// 1. Test Successful Login (Correct 'email' Payload)
echo "\nTest 1: Login with correct payload (email key)...\n";
$response = $client->post('/api/auth/login', [
    'json' => [
        'email' => 'admin@college-abc.bf',
        'password' => 'Abc12345!',
        'role' => 'direction'
    ]
]);

$statusCode = $response->getStatusCode();
$body = json_decode($response->getBody(), true);
$authToken = null;

if ($statusCode === 200 && isset($body['token'])) {
    echo "✅ PASSED: Login successful. Token received.\n";
    $authToken = $body['token'];
} else {
    echo "❌ FAILED: Received status $statusCode.\n";
    print_r($body);
}

// 2. Test Invalid Payload (Old 'identifier' key)
echo "\nTest 2: Login with incorrect payload (identifier key, missing email)...\n";
$response = $client->post('/api/auth/login', [
    'json' => [
        'identifier' => 'admin@college-abc.bf', // Wrong key
        'password' => 'Abc12345!',
        'role' => 'direction'
    ]
]);

$statusCode = $response->getStatusCode();
$body = json_decode($response->getBody(), true);

// Expect 422 because 'email' is required
if ($statusCode === 422) {
    echo "✅ PASSED: Backend correctly rejected missing email field (Status 422).\n";
} else {
    echo "❌ FAILED: Expected 422, got $statusCode.\n";
    print_r($body);
}

// 3. Test Protected Route (Students)
echo "\nTest 3: Fetch Students with Valid Token...\n";
if ($authToken) {
    try {
        $response = $client->get('/api/v1/students', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Accept' => 'application/json'
            ]
        ]);

        $statusCode = $response->getStatusCode();
        echo "Login Token used. Status: $statusCode\n";
        if ($statusCode === 200) {
            echo "✅ PASSED: Access to Students API successful.\n";
        } else {
            echo "❌ FAILED: Access denied with status $statusCode.\n";
            echo "Response Body: " . $response->getBody() . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ FAILED: Exception: " . $e->getMessage() . "\n";
        if ($e instanceof GuzzleHttp\Exception\ClientException) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
    }
} else {
    echo "Skipping Test 3 (No token available).\n";
}

// 4. Test Protected Route (Me)
echo "\nTest 4: Fetch /api/auth/me with Valid Token...\n";
if ($authToken) {
    try {
        $response = $client->get('/api/auth/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Accept' => 'application/json'
            ]
        ]);

        $statusCode = $response->getStatusCode();
        echo "Login Token used. Status: $statusCode\n";
        if ($statusCode === 200) {
            echo "✅ PASSED: Access to Auth/Me successful.\n";
            echo "Response Body: " . $response->getBody() . "\n";
        } else {
            echo "❌ FAILED: Access denied with status $statusCode.\n";
            echo "Response Body: " . $response->getBody() . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ FAILED: Exception: " . $e->getMessage() . "\n";
        if ($e instanceof GuzzleHttp\Exception\ClientException) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
    }
} else {
    echo "Skipping Test 4 (No token available).\n";
}

// 5. Test Protected Route (Academic)
echo "\nTest 5: Fetch Academic Current Year...\n";
if ($authToken) {
    try {
        $response = $client->get('/api/v1/academic-years/current', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Accept' => 'application/json'
            ]
        ]);

        $statusCode = $response->getStatusCode();
        echo "Login Token used. Status: $statusCode\n";
        if ($statusCode === 200) {
            echo "✅ PASSED: Access to Academic/Current successful.\n";
            echo "Response Body: " . substr($response->getBody(), 0, 500) . "...\n";
        } else {
            echo "❌ FAILED: Access denied via Academic/Current with status $statusCode.\n";
            echo "Response Body: " . substr($response->getBody(), 0, 500) . "...\n";
        }
    } catch (\Exception $e) {
        // It might be 404 if data missing or 403 if permission denied
        echo "❌ FAILED: Exception: " . $e->getMessage() . "\n";
        if ($e instanceof GuzzleHttp\Exception\ClientException) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
    }
} else {
    echo "Skipping Test 5 (No token available).\n";
}

// 6. Test Protected Route (Classes)
echo "\nTest 6: Fetch Classes...\n";
if ($authToken) {
    try {
        $response = $client->get('/api/v1/classes', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Accept' => 'application/json'
            ]
        ]);

        $statusCode = $response->getStatusCode();
        echo "Login Token used. Status: $statusCode\n";
        if ($statusCode === 200) {
            echo "✅ PASSED: Access to Classes successful.\n";
            echo "Response Body: " . substr($response->getBody(), 0, 500) . "...\n";
        } else {
            echo "❌ FAILED: Access denied via Classes with status $statusCode.\n";
            echo "Response Body: " . substr($response->getBody(), 0, 500) . "...\n";
        }
    } catch (\Exception $e) {
        echo "❌ FAILED: Exception: " . $e->getMessage() . "\n";
        if ($e instanceof GuzzleHttp\Exception\ClientException) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
    }
} else {
    echo "Skipping Test 6 (No token available).\n";
}

// 7. Test Protected Route (Finance)
echo "\nTest 7: Fetch Fee Types...\n";
if ($authToken) {
    try {
        $response = $client->get('/api/v1/fee-types', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Accept' => 'application/json'
            ]
        ]);

        $statusCode = $response->getStatusCode();
        echo "Login Token used. Status: $statusCode\n";
        if ($statusCode === 200) {
            echo "✅ PASSED: Access to Fee Types successful.\n";
            echo "Response Body: " . substr($response->getBody(), 0, 500) . "...\n";
        } else {
            echo "❌ FAILED: Access denied via Fee Types with status $statusCode.\n";
            echo "Response Body: " . substr($response->getBody(), 0, 500) . "...\n";
        }
    } catch (\Exception $e) {
        echo "❌ FAILED: Exception: " . $e->getMessage() . "\n";
        if ($e instanceof GuzzleHttp\Exception\ClientException) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
    }
} else {
    echo "Skipping Test 7 (No token available).\n";
}

// 8. Test Protected Route (Grades)
echo "\nTest 8: Fetch Grades for a Student...\n";
if ($authToken) {
    try {
        // Step 1: Get a student ID
        $respStudents = $client->get('/api/v1/students?per_page=1', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Accept' => 'application/json'
            ]
        ]);
        $studentsData = json_decode($respStudents->getBody(), true);
        $studentId = $studentsData['data'][0]['id'] ?? null;

        if ($studentId) {
            echo "Found Student ID: $studentId\n";

            // Step 2: Fetch grades for this student
            $response = $client->get("/api/v1/grades/by-student/$studentId", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            echo "Login Token used. Status: $statusCode\n";
            if ($statusCode === 200) {
                echo "✅ PASSED: Access to Grades by Student successful.\n";
                // echo "Response Body: " . substr($response->getBody(), 0, 500) . "...\n";
                $body = json_decode($response->getBody(), true);
                if (isset($body['data']) && is_array($body['data'])) {
                    echo "Grades found: " . count($body['data']) . "\n";
                } else {
                    echo "Format warning: 'data' is not an array.\n";
                }
            } else {
                echo "❌ FAILED: Access denied via Grades with status $statusCode.\n";
            }

            // Step 3: Test Report Card Generation
            echo "\nTest 9: Generate Report Card PDF for Student $studentId...\n";
            try {
                // Assuming academic year ID is 1 (from seeder) or fetch current
                $academicYearId = 1;
                $responseCard = $client->post("/api/v1/grades/student/$studentId/report-card", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $authToken,
                        'Accept' => 'application/json'
                    ],
                    'json' => [
                        'academic_year_id' => $academicYearId,
                        'period' => 'T1' // Example period
                    ]
                ]);
                $statusCard = $responseCard->getStatusCode();
                echo "...Generation request sent. Status: $statusCard\n";
                if ($statusCard === 200 || $statusCard === 201) {
                    echo "✅ PASSED: Report Card generation endpoint is reachable.\n";
                    // Check content type or something?
                } else {
                    echo "❌ FAILED with status $statusCard\n";
                    echo "Response: " . substr($responseCard->getBody(), 0, 300) . "\n";
                }
            } catch (\Exception $e) {
                echo "⚠️ Report Card Generation check failed (could be config/library issue): " . $e->getMessage() . "\n";
                if ($e instanceof GuzzleHttp\Exception\ClientException) {
                    echo "Response: " . $e->getResponse()->getBody() . "\n";
                }
            }
        } else {
            echo "⚠️ SKIPPED: No students found to test grades.\n";
        }
    } catch (\Exception $e) {
        echo "❌ FAILED: Exception: " . $e->getMessage() . "\n";
        if ($e instanceof GuzzleHttp\Exception\ClientException) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
    }
} else {
    echo "Skipping Test 8 (No token available).\n";
}

echo "\nTest Complete.\n";
