$controller = new \App\Http\Controllers\EnrollmentController();
$request = \Illuminate\Http\Request::create('/api/v1/enroll', 'POST', [
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

try {
$response = $controller->store($request);
echo "Response Code: " . $response->getStatusCode() . "\n";
echo "Response Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
echo "Exception: " . $e->getMessage() . "\n";
echo $e->getTraceAsString();
}
exit;