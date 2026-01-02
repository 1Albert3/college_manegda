<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'test@test.bf';
$classId = 'a0b940aa-4026-4ca7-aee1-45c446044b8b'; // 2nde A

echo "Diagnosing visibility for user: $email\n";
echo "Target Class: 2nde A ($classId)\n";
echo "------------------------------------------------\n";

// 1. Check User
$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "❌ User not found.\n";
    exit;
}
echo "✅ User found (ID: {$user->id})\n";

// 2. Check Teacher Profile (Lycee)
$teacherLycee = \Illuminate\Support\Facades\DB::connection('school_lycee')
    ->table('teachers_lycee')
    ->where('user_id', $user->id)
    ->first();

if (!$teacherLycee) {
    echo "❌ No TeacherLycee profile found for this user.\n";
    echo "   -> This means the system doesn't know this user is a Lycée teacher.\n";
} else {
    echo "✅ TeacherLycee profile found (ID: {$teacherLycee->id})\n";

    // 3. Check Professor Principal
    $class = \App\Models\Lycee\ClassLycee::find($classId);
    $isPP = ($class->prof_principal_id === $teacherLycee->id);
    echo ($isPP ? "✅" : "ℹ️") . " Is Prof Principal? " . ($isPP ? "YES" : "NO") . "\n";

    // 4. Check Subject Assignments
    $assignments = \Illuminate\Support\Facades\DB::connection('school_lycee')
        ->table('teacher_subject_assignments')
        ->where('teacher_id', $teacherLycee->id)
        ->where('class_id', $classId)
        ->get();

    if ($assignments->isEmpty()) {
        echo "❌ No subject assignments found for this class.\n";
    } else {
        echo "✅ Found " . $assignments->count() . " assignment(s):\n";
        foreach ($assignments as $a) {
            echo "   - Subject ID: {$a->subject_id}\n";
        }
    }
}

echo "------------------------------------------------\n";
// 5. Check Global Visibility (What the Dashboard Controller sees)
$controller = new \App\Http\Controllers\Dashboard\TeacherDashboardController();

// Hack to simulate request user
$req = new \Illuminate\Http\Request();
$req->setUserResolver(function () use ($user) {
    return $user;
});

echo "Dashboard Controller Logic Check:\n";
// We reuse the logic from formatClass inside the logic trace
\App\Models\SchoolYear::current(); // Ensure current year is loaded
$schoolYearId = \App\Models\SchoolYear::current()->id;

if ($teacherLycee) {
    $assignedClassIds = \Illuminate\Support\Facades\DB::connection('school_lycee')
        ->table('teacher_subject_assignments')
        ->where('teacher_id', $teacherLycee->id)
        ->where('school_year_id', $schoolYearId)
        ->pluck('class_id');

    echo "DASHBOARD SEES: Assigned Class IDs for this teacher: " . $assignedClassIds->implode(', ') . "\n";

    if ($assignedClassIds->contains($classId)) {
        echo "✅ Logic confirms 2nde A is in the list.\n";
    } else {
        echo "❌ Logic says 2nde A is NOT in the list.\n";
    }
}
