<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollmentController;

try {
    $controller = new EnrollmentController();

    $request = Request::create('/api/v1/enroll', 'POST', [
        'student' => [
            'firstName' => 'Albert',
            'lastName' => 'NABA',
            'birthDate' => '2005-01-01',
            'birthPlace' => 'Ouagadougou',
            'gender' => 'M',
            'address' => 'Secteur 12',
            'requestedClass' => 'Tle D1 D'
        ],
        'parents' => [
            'fatherName' => 'NABA Senior',
            'email' => 'parent.naba@test.com',
            'fatherPhone' => '70000000',
            'address' => 'Secteur 12'
        ]
    ]);

    $response = $controller->store($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
