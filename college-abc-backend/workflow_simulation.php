<?php

use App\Models\User;
use App\Models\Lycee\StudentLycee;
use App\Models\Lycee\EnrollmentLycee;
use App\Models\Lycee\ClassLycee;
use App\Models\Lycee\SubjectLycee;
use App\Models\Lycee\TeacherLycee;
use App\Models\Finance\Invoice;
use App\Models\Finance\Payment;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Lycee\ReportCardLyceeController;
use Illuminate\Http\Request;

echo "--- Starting Workflow Simulation ---\n";

// 1. Find Student
$student = StudentLycee::where('nom', 'NABA')->first();
if (!$student) {
    echo "Student 'NABA' not found.\n";
    exit(1);
}
echo "Student Found: {$student->nom} {$student->prenoms} ({$student->matricule})\n";

// 2. Validate Enrollment
$enrollment = EnrollmentLycee::where('student_id', $student->id)->first();
if (!$enrollment) {
    echo "Enrollment not found. Creating one manually...\n";
    $year = SchoolYear::current() ?? SchoolYear::latest()->first();
    // Find class
    $class = ClassLycee::where('nom', 'LIKE', '%Tle D%')->first();

    $enrollment = EnrollmentLycee::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'school_year_id' => $year->id,
        'statut' => 'en_attente',
        'date_inscription' => now(),
        'frais_scolarite' => 325000,
        'total_a_payer' => 325000,
        // Ensure new columns are set if needed (defaults should work)
    ]);
    echo "Enrollment Created Manually (ID: {$enrollment->id})\n";
}
$enrollment->statut = 'validee';
$enrollment->save();
echo "Enrollment Validated (ID: {$enrollment->id})\n";

// 3. Create Invoice
// Check if invoice exists
$invoice = Invoice::where('student_id', $student->id)->where('type', 'inscription')->first();
if (!$invoice) {
    $invoice = Invoice::create([
        'student_id' => $student->id,
        'student_database' => 'school_lycee',
        'enrollment_id' => $enrollment->id,
        'school_year_id' => $enrollment->school_year_id,
        'type' => 'inscription',
        'description' => 'Frais de scolarité - Inscription',
        'montant_ht' => 325000,
        'montant_ttc' => 325000,
        'montant_paye' => 0,
        'solde' => 325000,
        'statut' => 'emise',
        'date_emission' => now(),
        'date_echeance' => now()->addMonths(1),
        'created_by' => User::where('email', 'comptabilite@wend-manegda.bf')->value('id')
    ]);
    echo "Invoice Created (ID: {$invoice->id})\n";
} else {
    echo "Invoice Exists (ID: {$invoice->id})\n";
}

// 4. Pay Invoice
if ($invoice->statut !== 'payee') {
    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'student_id' => $student->id,
        'student_database' => 'school_lycee',
        'montant' => 325000,
        'mode_paiement' => 'especes',
        'date_paiement' => now(),
        'reference_transaction' => 'CASH-001',
        'statut' => 'valide',
        'validated_by' => User::where('email', 'comptabilite@wend-manegda.bf')->value('id'),
        'validated_at' => now(),
        'received_by' => User::where('email', 'comptabilite@wend-manegda.bf')->value('id'),
    ]);

    $invoice->updateStatus();
    echo "Payment Created & Invoice Updated to: {$invoice->statut}\n";

    // Update Enrollment financial status manually (as assumed required)
    $enrollment->montant_paye = 325000;
    $enrollment->solde_restant = 0;
    $enrollment->save();
    echo "Enrollment Financials Updated\n";
} else {
    echo "Invoice already paid.\n";
}

// 5. Teacher & Grades
// Find Tle D class
$class = ClassLycee::find($enrollment->class_id);
echo "Class: {$class->nom}\n";

// Find Teacher (Drabo)
// Seeder says: 'nom' => 'DRABO', 'prenoms' => 'Aristide' ? Or check seed data.
// Let's find any teacher or create one.
$teacherUser = User::where('email', 'drabo.aristide@college-abc.com')->first();
if (!$teacherUser) {
    // Try generic
    $teacherUser = User::where('role', 'enseignant')->first();
}
// Find TeacherLycee record
$teacher = TeacherLycee::where('user_id', $teacherUser->id)->first();
if (!$teacher) {
    // Create a dummy teacher record if missing for this user
    $teacher = TeacherLycee::create([
        'user_id' => $teacherUser->id,
        'matricule' => 'ENS-TEST-' . rand(100, 999),
        'date_embauche' => now(),
        'type_contrat' => 'permanent'
    ]);
    echo "Created Teacher Profile for " . $teacherUser->email . "\n";
}

// Find Subject (Philosophie)
$subject = SubjectLycee::where('nom', 'LIKE', '%Philo%')->first();
if (!$subject) {
    // Create Philosophy
    $subject = SubjectLycee::create([
        'code' => 'PHI',
        'nom' => 'Philosophie',
        'coefficient_tle_D' => 2,
        'is_active' => true
    ]);
    echo "Created Subject Philosophie\n";
} else {
    echo "Subject found: {$subject->nom}\n";
}

// Assign Teacher to Subject in Class
$assignment = DB::connection('school_lycee')->table('teacher_subject_assignments')
    ->where('teacher_id', $teacher->id)
    ->where('subject_id', $subject->id)
    ->where('class_id', $class->id)
    ->first();

if (!$assignment) {
    DB::connection('school_lycee')->table('teacher_subject_assignments')->insert([
        'id' => \Illuminate\Support\Str::uuid(),
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'class_id' => $class->id,
        'school_year_id' => $enrollment->school_year_id,
        'heures_par_semaine' => 4,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Assigned Teacher to Class/Subject\n";
}

// Add Grade (15/20)
// Check if grade exists
$grade = DB::connection('school_lycee')->table('grades_lycee')
    ->where('student_id', $student->id)
    ->where('subject_id', $subject->id)
    ->first();

if (!$grade) {
    DB::connection('school_lycee')->table('grades_lycee')->insert([
        'id' => \Illuminate\Support\Str::uuid(),
        'student_id' => $student->id,
        'class_id' => $class->id,
        'subject_id' => $subject->id,
        'school_year_id' => $enrollment->school_year_id,
        // 'teacher_id' => $teacher->id, // Column does not exist
        'recorded_by' => $teacher->user_id, // Use User ID of teacher
        'note_obtenue' => 15, // Correct column
        'note_sur' => 20, // Correct column
        'note_sur_20' => 15, // Correct column
        'coefficient' => $subject->coefficient_tle_D ?? 2,
        'type_evaluation' => 'IE', // 'devoir' is not in enum ['IE', 'DS', 'Comp', 'TP', 'CC']
        'trimestre' => '1', // Correct column and value
        'date_evaluation' => now(),
        'commentaire' => 'Bien', // Correct column
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Grade Added: 15/20\n";
} else {
    echo "Grade already exists.\n";
}

// 6. Generate Report Card
echo "Generating Report Card...\n";
try {
    // Check school year
    $schoolYear = \App\Models\SchoolYear::current();
    if (!$schoolYear) {
        echo "No current school year!\n";
        $schoolYear = \App\Models\SchoolYear::latest()->first();
    }
    echo "Using School Year: {$schoolYear->id}\n";

    $service = app(\App\Services\Lycee\ReportCardLyceeService::class);
    $bulletin = $service->generateForStudent(
        $student->id,
        $class->id,
        $schoolYear->id,
        1 // Trimestre integer
    );

    echo "Service returned bulletin ID: " . ($bulletin->id ?? 'null') . "\n";

    // Check if bulletin exists
    $bulletinCheck = DB::connection('school_lycee')->table('report_cards_lycee')
        ->where('student_id', $student->id)
        ->where('trimestre', '1')
        ->first();

    if ($bulletin) {
        echo "Bulletin Generated Successfully (ID: {$bulletin->id}, Moyenne: {$bulletin->moyenne_generale})\n";
    } else {
        echo "Bulletin Generation Failed. Found " . DB::connection('school_lycee')->table('report_cards_lycee')->where('student_id', $student->id)->count() . " bulletins for this student.\n";
        // Dump all bulletins
        $all = DB::connection('school_lycee')->table('report_cards_lycee')->where('student_id', $student->id)->get();
        foreach ($all as $b) {
            echo " - ID: {$b->id}, Trimestre: {$b->trimestre}, Year: {$b->school_year_id}\n";
        }
    }
} catch (\Exception $e) {
    echo "Error generating bulletin: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "--- Workflow Simulation Completed ---\n";
