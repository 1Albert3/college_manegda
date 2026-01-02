<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolYear;
use App\Models\MP\StudentMP;
use App\Models\College\StudentCollege;
use App\Models\Lycee\StudentLycee;
use App\Models\MP\ClassMP;
use App\Models\College\ClassCollege;
use App\Models\Lycee\ClassLycee;
use App\Models\MP\EnrollmentMP;
use App\Models\College\EnrollmentCollege;
use App\Models\Lycee\EnrollmentLycee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UniversalStudentController extends Controller
{
    /**
     * Create a student in any cycle based on the class name.
     */
    public function store(Request )
    {
        // 1. Validation for the simplified frontend form
         = ->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'birth_date' => 'required|date',
            'gender' => 'required|in:M,F',
            'parent_name' => 'required|string',
            'parent_phone' => 'required|string',
            'class_name' => 'required|string',
            'status' => 'nullable|string'
        ]);

         = ['class_name'];
        
        // 2. Identify Cycle and Class
         = ->detectCycle();
         = ->findClass(, );

        if (targetClass) {
            return response()->json([
                'error' => 'Classe introuvable',
                'message' => 'La classe demandée n\'existe pas.'
            ], 422);
        }

        // 3. Create Student and Enrollment in Transaction
        return DB::transaction(function () use (, , ) {
             = date('y');
             =  . '-' . strtoupper(substr(['last_name'], 0, 3)) . '-' . rand(1000, 9999);

             = [
                'matricule' => ,
                'nom' => ['last_name'],
                'prenoms' => ['first_name'],
                'date_naissance' => ['birth_date'],
                'lieu_naissance' => 'Burkina Faso', // Default
                'sexe' => ['gender'],
                'statut_inscription' => 'nouveau',
                'is_active' => true
            ];

             = null;
             = null;
             = SchoolYear::current();

            if (schoolYear) {
                throw new \Exception('Aucune année scolaire active.');
            }

            if ( === 'mp') {
                 = StudentMP::create();
                 = EnrollmentMP::create([
                    'student_id' => ->id,
                    'class_id' => ->id,
                    'school_year_id' => ->id,
                    'statut' => ['status'] === 'active' ? 'validee' : 'en_attente',
                    'date_inscription' => now(),
                    'type_inscription' => 'reinscription' // Default
                ]);
            } elseif ( === 'college') {
                 = StudentCollege::create();
                 = EnrollmentCollege::create([
                    'student_id' => ->id,
                    'class_id' => ->id,
                    'school_year_id' => ->id,
                    'statut' => ['status'] === 'active' ? 'validee' : 'en_attente',
                    'date_inscription' => now(),
                    'type_inscription' => 'reinscription'
                ]);
            } elseif ( === 'lycee') {
                 = StudentLycee::create();
                 = EnrollmentLycee::create([
                    'student_id' => ->id,
                    'class_id' => ->id,
                    'school_year_id' => ->id,
                    'statut' => ['status'] === 'active' ? 'validee' : 'en_attente',
                    'date_inscription' => now(),
                    'type_inscription' => 'reinscription'
                ]);
            }

            // Note: Parent/Guardian logic is simplified here (omitted for now to avoid complexity, 
            // but ideally we should create a Guardian record linked to the student)

            return response()->json([
                'message' => 'Inscription réussie',
                'student' => ,
                'enrollment' => ,
                'cycle' => 
            ], 201);
        });
    }

    private function detectCycle()
    {
         = strtoupper();
        // CP1, CP2, CE1, CE2, CM1, CM2, PS, MS, GS
        if (preg_match('/^(CP|CE|CM|PS|MS|GS)/', )) {
            return 'mp';
        }
        // 6eme, 5eme, 4eme, 3eme
        if (preg_match('/[6543].*ME/i', ) || preg_match('/[6543].*ème/i', )) {
            return 'college';
        }
        // 2nd, 1ere, Tle
        if (preg_match('/^(2|1|T)/', )) {
            return 'lycee';
        }
        return 'college'; // Default fallback
    }

    private function findClass(, )
    {
        if ( === 'mp') return ClassMP::where('nom', )->first();
        if ( === 'college') return ClassCollege::where('nom', )->first();
        if ( === 'lycee') return ClassLycee::where('nom', )->first();
        return null;
    }
}

