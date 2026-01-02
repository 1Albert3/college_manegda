<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

echo "Starting Assignment...\n";

// 1. Find Class
try {
    $class = DB::connection('school_college')->table('classes_college')->where('nom', 'LIKE', '%C Test%')->first();
} catch (\Exception $e) {
    die("Error connecting to College DB: " . $e->getMessage() . "\n");
}

if (!$class) die("Class C Test not found.\n");
echo "Class: " . $class->nom . "\n";

// 2. Find or Create Teacher
$teacher = DB::connection('school_college')->table('teachers_college')->first();

if (!$teacher) {
    echo "Creating Teacher...\n";
    $userId = Str::uuid()->toString();

    // Create User (check duplication by email)
    $email = 'prof.testeur@college.bf';
    $existingUser = DB::table('users')->where('email', $email)->first();

    if ($existingUser) {
        $userId = $existingUser->id;
    } else {
        DB::table('users')->insert([
            'id' => $userId,
            'first_name' => 'Prof',
            'last_name' => 'TESTEUR',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'enseignant',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    // Create Teacher Record
    $teacherId = Str::uuid()->toString();

    // Check if table has date_embauche
    try {
        DB::connection('school_college')->table('teachers_college')->insert([
            'id' => $teacherId,
            'user_id' => $userId,
            'matricule' => 'ENS-COL-TEST',
            'specialites' => json_encode(['Mathématiques']),
            'statut' => 'actif',
            'date_embauche' => now(), // Ajout date_embauche
            'type_contrat' => 'permanent',  // Changed from vacataire to permanent
            'created_at' => now(),
            'updated_at' => now()
        ]);
    } catch (\Exception $e) {
        die("Error creating teacher: " . $e->getMessage() . "\n");
    }

    $teacher = DB::connection('school_college')->table('teachers_college')->where('id', $teacherId)->first();
}

echo "Teacher ID: " . $teacher->id . "\n";

// Get Teacher User for login info
$teacherUser = DB::table('users')->where('id', $teacher->user_id)->first();
echo "Teacher Email: " . ($teacherUser->email ?? 'N/A') . "\n";

// 3. Find Subject
$subject = DB::connection('school_college')->table('subjects_college')->where('code', 'MAT')->first();
if (!$subject) {
    echo "Creating Subject MAT...\n";
    $subjectId = Str::uuid()->toString();
    DB::connection('school_college')->table('subjects_college')->insert([
        'id' => $subjectId,
        'code' => 'MAT',
        'nom' => 'Mathématiques',
        'coefficient_6eme' => 5,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    $subject = DB::connection('school_college')->table('subjects_college')->where('id', $subjectId)->first();
}
echo "Subject: " . $subject->nom . "\n";

// 4. Find Year
$year = DB::table('school_years')->where('is_current', true)->first();
if (!$year) die("No active year.\n");

try {
    // Check duplication
    $exists = DB::connection('school_college')->table('teacher_subject_assignments')
        ->where('teacher_id', $teacher->id)
        ->where('subject_id', $subject->id)
        ->where('class_id', $class->id)
        ->where('school_year_id', $year->id)
        ->exists();

    if (!$exists) {
        DB::connection('school_college')->table('teacher_subject_assignments')->insert([
            'id' => Str::uuid()->toString(),
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'school_year_id' => $year->id,
            'heures_par_semaine' => 5,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "SUCCESS: Assigned Teacher to Class.\n";
    } else {
        echo "INFO: Assignment already exists.\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
