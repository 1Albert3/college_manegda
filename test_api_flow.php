<?php

require __DIR__ . '/college-abc-backend/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

$client = new Client([
    'base_uri' => 'http://localhost:8000/api/v1/',
    'http_errors' => false
]);

try {
    echo "1. Testing Login...\n";
    $response = $client->post('auth/login', [
        'json' => [
            'identifier' => 'direction@wend-manegda.bf',
            'password' => 'directeur'
        ]
    ]);

    $body = json_decode($response->getBody(), true);
    if ($response->getStatusCode() === 200 && ($body['success'] ?? false)) {
        echo "Login Successful. Token received.\n";
        $token = $body['access_token'];
    } else {
        echo "Login Failed: " . $response->getBody() . "\n";
        exit(1);
    }

    echo "\n2. Testing Dashboard Overview...\n";
    $response = $client->get('dashboard/direction', [
        'headers' => ['Authorization' => 'Bearer ' . $token]
    ]);
    if ($response->getStatusCode() === 200) {
        echo "Dashboard Overview OK.\n";
        $data = json_decode($response->getBody(), true);
        echo "Total Students: " . ($data['overview']['total_students'] ?? 'N/A') . "\n";
    } else {
        echo "Dashboard Failed: " . $response->getStatusCode() . "\n";
    }

    echo "\n3. Testing Student Creation (COLLEGE)...\n";

    // Récupérer une classe collège
    echo "   Fetching College Classes...\n";
    $respComp = $client->get('college/classes', ['headers' => ['Authorization' => 'Bearer ' . $token]]);
    $classes = json_decode($respComp->getBody(), true);
    $classId = $classes['data'][0]['id'] ?? null;

    if (!$classId) {
        echo "   No college class found. Skipping creation test.\n";
    } else {
        echo "   Found Class ID: $classId\n";

        $studentData = [
            'nom' => 'TEST_AUTO',
            'prenoms' => 'Jean ' . rand(100, 999),
            'date_naissance' => '2010-01-01',
            'lieu_naissance' => 'Ouagadougou',
            'sexe' => 'M',
            'nationalite' => 'Burkinabè',
            'regime' => 'externe',
            'mode_paiement' => 'comptant',
            'class_id' => $classId,
            'school_year_id' => $data['school_year']['id'] ?? null,
            'pere' => [
                'nom_complet' => 'Pere TEST',
                'telephone_1' => '70000000',
                'adresse_physique' => 'Secteur 1'
            ]
        ];

        $respCreate = $client->post('college/enrollments', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => $studentData
        ]);

        if ($respCreate->getStatusCode() === 201) {
            echo "Student Created Successfully!\n";
            echo $respCreate->getBody() . "\n";
        } else {
            echo "Student Creation Failed: " . $respCreate->getStatusCode() . "\n";
            echo "Response Body: " . $respCreate->getBody()->getContents() . "\n";
        }
    }
} catch (ClientException $e) {
    echo "Client Exception: " . $e->getMessage() . "\n";
    if ($e->hasResponse()) {
        echo "Response: " . $e->getResponse()->getBody()->getContents() . "\n";
    }
} catch (\Exception $e) {
    echo "General Exception: " . $e->getMessage() . "\n";
}
