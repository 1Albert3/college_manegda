<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\SchoolYear;
use App\Models\Finance\Invoice;

// MP Models
use App\Models\MP\StudentMP;
use App\Models\MP\ClassMP;
use App\Models\MP\EnrollmentMP;
use App\Models\MP\GuardianMP;

// College Models
use App\Models\College\StudentCollege;
use App\Models\College\ClassCollege;
use App\Models\College\EnrollmentCollege;
use App\Models\College\GuardianCollege;

// Lycee Models
use App\Models\Lycee\StudentLycee;
use App\Models\Lycee\ClassLycee;
use App\Models\Lycee\EnrollmentLycee;
use App\Models\Lycee\GuardianLycee;

class EnrollmentController extends Controller
{
    /**
     * Inscription complète d'un élève (Module 1 - Public & Secretary)
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            'student.firstName' => 'required|string|max:255',
            'student.lastName' => 'required|string|max:255',
            'student.birthDate' => 'required|date',
            'student.birthPlace' => 'required|string|max:255',
            'student.gender' => 'required|in:M,F',
            'student.address' => 'required|string|max:255',
            'student.requestedClass' => 'required|string',
            // Parents
            'parents.fatherName' => 'nullable|string',
            'parents.motherName' => 'nullable|string',
            'parents.email' => 'required|email',
            'parents.fatherPhone' => 'nullable|string',
            'parents.motherPhone' => 'nullable|string',
            'parents.address' => 'nullable|string'
        ]);

        $studentInfo = $validated['student'];
        $parentsInfo = $validated['parents'];

        $requestedClass = $studentInfo['requestedClass'];
        $cycle = $this->detectCycle($requestedClass);

        return DB::transaction(function () use ($studentInfo, $parentsInfo, $cycle, $requestedClass) {

            // 2. Resolve Class
            $targetClass = $this->findClass($requestedClass, $cycle);
            if (!$targetClass) {
                return response()->json([
                    'success' => false,
                    'message' => "Classe demandée introuvable: $requestedClass"
                ], 422);
            }

            // 3. Generate Matricule
            $yearSuffix = date('y');
            $prefix = strtoupper(substr($cycle, 0, 3)); // MP-, COL-, LYC-
            if ($cycle === 'college') $prefix = 'COL';
            if ($cycle === 'lycee') $prefix = 'LYC';
            if ($cycle === 'mp') $prefix = 'MP';

            $matricule = $prefix . '-' . $yearSuffix . '-' . strtoupper(substr($studentInfo['lastName'], 0, 3)) . '-' . rand(1000, 9999);

            // 4. Create Users
            // Parent User
            $parentName = $parentsInfo['fatherName'] ?: $parentsInfo['motherName'] ?: 'Parent';
            // Simple split for first/last name
            $parts = explode(' ', trim($parentName), 2);
            $parentLastName = $parts[0] ?? 'Parent';
            $parentFirstName = $parts[1] ?? 'Tuteur';

            $parentUser = User::firstOrCreate(
                ['email' => $parentsInfo['email']],
                [
                    'first_name' => $parentFirstName,
                    'last_name' => $parentLastName,
                    'password' => Hash::make('password123'),
                    'role' => 'parent'
                ]
            );

            // Student User
            // Check if matricule exists as email/username? Using email format for login usually?
            // Requirement: "identifiant = matricule"
            // We'll create a user with email = matricule@college.bf (fake email) or just use matricule if login supports it.
            // Assuming login uses email field.
            $studentUser = User::create([
                'first_name' => $studentInfo['firstName'],
                'last_name' => $studentInfo['lastName'],
                'email' => strtolower($matricule) . '@student.college.bf', // Unique identifier
                'matricule' => $matricule, // Ensure matricule is saved in User if column exists (checked User model, it does)
                'password' => Hash::make('password123'),
                'role' => 'eleve' // 'eleve' or 'student'? Seeder says 'eleve' (line 73)
            ]);

            // 5. Create Student Record
            $studentData = [
                'user_id' => $studentUser->id,
                'matricule' => $matricule,
                'nom' => $studentInfo['lastName'],
                'prenoms' => $studentInfo['firstName'],
                'date_naissance' => $studentInfo['birthDate'],
                'lieu_naissance' => $studentInfo['birthPlace'],
                'sexe' => $studentInfo['gender'],
                'is_active' => true,
                'statut_inscription' => 'nouveau'
            ];

            // Add fields specific to models if needed (address is not always in fillable, check model)
            // But we will stick to fillable.

            $student = null;
            $enrollment = null;
            $schoolYear = SchoolYear::where('is_current', true)->first() ?? SchoolYear::latest()->first();


            $guardianType = 'tuteur';
            if (!empty($parentsInfo['fatherName'])) $guardianType = 'pere';
            elseif (!empty($parentsInfo['motherName'])) $guardianType = 'mere';

            if ($cycle === 'mp') {
                $student = StudentMP::create($studentData);
                $enrollment = EnrollmentMP::create([
                    'student_id' => $student->id,
                    'class_id' => $targetClass->id,
                    'school_year_id' => $schoolYear->id,
                    'statut' => 'en_attente',
                    'date_inscription' => now(),
                    'type_inscription' => 'inscription',
                    'frais_scolarite' => 250000, // Example
                    'total_a_payer' => 250000
                ]);

                // Create Guardian
                GuardianMP::create([
                    'student_id' => $student->id,
                    'user_id' => $parentUser->id,
                    'type' => $guardianType,
                    'nom_complet' => $parentLastName . ' ' . $parentFirstName,
                    'email' => $parentsInfo['email'],
                    'telephone_1' => $parentsInfo['fatherPhone'] ?? $parentsInfo['motherPhone'],
                    'adresse_physique' => $parentsInfo['address']
                ]);
            } elseif ($cycle === 'college') {
                $student = StudentCollege::create($studentData);
                $enrollment = EnrollmentCollege::create([
                    'student_id' => $student->id,
                    'class_id' => $targetClass->id,
                    'school_year_id' => $schoolYear->id,
                    'statut' => 'en_attente',
                    'date_inscription' => now(),
                    // Default financial values
                    'frais_scolarite' => 250000,
                    'total_a_payer' => 250000
                ]);

                GuardianCollege::create([
                    'student_id' => $student->id,
                    'user_id' => $parentUser->id,
                    'type' => $guardianType,
                    'nom_complet' => $parentLastName . ' ' . $parentFirstName,
                    'email' => $parentsInfo['email'],
                    'telephone_1' => $parentsInfo['fatherPhone'] ?? $parentsInfo['motherPhone'],
                    'adresse_physique' => $parentsInfo['address']
                ]);
            } elseif ($cycle === 'lycee') {
                $student = StudentLycee::create($studentData);
                $enrollment = EnrollmentLycee::create([
                    'student_id' => $student->id,
                    'class_id' => $targetClass->id,
                    'school_year_id' => $schoolYear->id,
                    'statut' => 'en_attente',
                    'date_inscription' => now(),
                    'frais_scolarite' => 325000,
                    'total_a_payer' => 325000
                ]);

                GuardianLycee::create([
                    'student_id' => $student->id,
                    'user_id' => $parentUser->id,
                    'type' => $guardianType,
                    'nom_complet' => $parentLastName . ' ' . $parentFirstName,
                    'email' => $parentsInfo['email'],
                    'telephone_1' => $parentsInfo['fatherPhone'] ?? $parentsInfo['motherPhone'],
                    'adresse_physique' => $parentsInfo['address']
                ]);

                // Create Invoice for Lycee
                Invoice::create([
                    'student_id' => $student->id,
                    'student_database' => 'school_lycee',
                    'enrollment_id' => $enrollment->id,
                    'school_year_id' => $schoolYear->id,
                    'type' => 'inscription',
                    'description' => "Frais de scolarité Classe {$targetClass->nom}",
                    'montant_ht' => $enrollment->total_a_payer,
                    'montant_ttc' => $enrollment->total_a_payer,
                    'montant_paye' => 0,
                    'solde' => $enrollment->total_a_payer,
                    'statut' => 'emise',
                    'date_emission' => now(),
                    'date_echeance' => now()->addMonths(1),
                    'created_by' => auth()->id() ?? User::where('email', 'secretariat@wend-manegda.bf')->first()?->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Compte élève et parent créés.',
                'data' => [
                    'matricule' => $matricule,
                    'student_email' => $studentUser->email,
                    'student_id' => $student->id
                ]
            ], 201);
        });
    }

    private function detectCycle($className)
    {
        $normalized = strtoupper($className);
        if (preg_match('/^(CP|CE|CM|PS|MS|GS)/', $normalized)) return 'mp';
        if (preg_match('/[6543].*ME/i', $className) || preg_match('/[6543].*ème/i', $className)) return 'college';
        if (preg_match('/^(2|1|T)/', $normalized)) return 'lycee';
        return 'college';
    }

    private function findClass($className, $cycle)
    {
        if ($cycle === 'mp') return ClassMP::where('nom', 'LIKE', "%$className%")->first();
        if ($cycle === 'college') return ClassCollege::where('nom', 'LIKE', "%$className%")->orWhere('nom', $className)->first();
        if ($cycle === 'lycee') {
            // "Tle D" might match "Terminale D" or "Tle D". Use LIKE.
            return ClassLycee::where('nom', 'LIKE', "%$className%")->orWhere('nom', $className)->first();
        }
        return null;
    }
}
