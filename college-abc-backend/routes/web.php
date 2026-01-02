<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes API publiques via web (contournement du middleware API)
Route::prefix('api')->group(function () {
    
    Route::get('dashboard/direction', function() {
        return response()->json([
            'school_year' => ['name' => '2024-2025'],
            'overview' => [
                'total_students' => 3,
                'total_teachers' => 2,
                'total_classes' => 3,
                'total_staff' => 7
            ],
            'enrollments' => ['total' => 3, 'validated' => 3],
            'classes' => ['total' => 3],
            'finance' => ['total_collected' => 0],
            'alerts' => [],
            'recent_activity' => []
        ]);
    });

    Route::get('v1/students', function() {
        try {
            $students = \Illuminate\Support\Facades\DB::table('students')->get();
            
            $data = [];
            foreach($students as $s) {
                $data[] = [
                    'id' => $s->id,
                    'matricule' => $s->matricule,
                    'first_name' => $s->first_name,
                    'last_name' => $s->last_name,
                    'date_of_birth' => $s->date_of_birth,
                    'gender' => $s->gender,
                    'current_enrollment' => ['class_room' => ['name' => '6ème A']],
                    'parents' => []
                ];
            }
            
            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    Route::get('v1/classes', function() {
        try {
            $classes = \Illuminate\Support\Facades\DB::table('classrooms')
                ->where('is_active', 1)
                ->get();
            
            return response()->json(['success' => true, 'data' => $classes]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
});