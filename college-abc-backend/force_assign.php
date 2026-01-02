<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teacher = App\Models\User::where('email', 'enseignant@wend-manegda.bf')->first();
if (!$teacher) die("Teacher not found\n");

// Ensure TeacherLycee
$teacherLycee = Illuminate\Support\Facades\DB::connection('school_lycee')->table('teachers_lycee')->where('user_id', $teacher->id)->first();
if (!$teacherLycee) {
    Illuminate\Support\Facades\DB::connection('school_lycee')->table('teachers_lycee')->insert([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'user_id' => $teacher->id,
        'matricule' => 'T-LYC-' . $teacher->id,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

$student = App\Models\Lycee\StudentLycee::where('last_name', 'like', '%Palou%')
    ->orWhere('first_name', 'like', '%Palou%')->first();
if (!$student) die("Student not found\n");
$classId = $student->classroom_id;

// Subject
$subj = Illuminate\Support\Facades\DB::connection('school_lycee')->table('subjects_lycee')->where('name', 'Mathématiques')->first();
if (!$subj) {
    echo "Creating Maths subject...\n";
    $id = \Illuminate\Support\Str::uuid()->toString();
    Illuminate\Support\Facades\DB::connection('school_lycee')->table('subjects_lycee')->insert([
        'id' => $id,
        'name' => 'Mathématiques',
        'code' => 'MATH',
        'coefficient' => 4,
        'series' => 'Toutes',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    $subj = (object)['id' => $id];
}

// Assign
$exists = Illuminate\Support\Facades\DB::connection('school_lycee')->table('teacher_subject_assignments')
    ->where('teacher_id', $teacher->id)
    ->where('class_id', $classId)
    ->where('subject_id', $subj->id)
    ->exists();

if (!$exists) {
    Illuminate\Support\Facades\DB::connection('school_lycee')->table('teacher_subject_assignments')->insert([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'teacher_id' => $teacher->id,
        'class_id' => $classId,
        'subject_id' => $subj->id,
        'school_year_id' => App\Models\SchoolYear::first()->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "ASSIGNATION EFFECTUEE\n";
} else {
    echo "DEJA ASSIGNE\n";
}
