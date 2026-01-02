<?php
require "../vendor/autoload.php";
$app = require "../bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

try {
    $students = \Illuminate\Support\Facades\DB::table("students")->get();
    
    $data = [];
    foreach($students as $s) {
        $data[] = [
            "id" => $s->id,
            "matricule" => $s->matricule,
            "first_name" => $s->first_name,
            "last_name" => $s->last_name,
            "date_of_birth" => $s->date_of_birth,
            "gender" => $s->gender,
            "current_enrollment" => ["class_room" => ["name" => "6ème A"]],
            "parents" => []
        ];
    }
    
    echo json_encode(["success" => true, "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
