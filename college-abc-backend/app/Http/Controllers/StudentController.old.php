<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * StudentController - Gestion des élèves
 * 
 * CRUD complet pour les élèves (base principale)
 */
class StudentController extends Controller
{
    use ApiResponse;
    /**
     * Liste des élèves avec filtres
     */
    public function index(Request $request)
    {
        try {
            $query = Student::with(['currentEnrollment.classroom', 'parents']);

            // Filtres
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('matricule', 'like', "%{$search}%");
                });
            }

            if ($request->has('gender') && $request->gender) {
                $query->where('gender', $request->gender);
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $students = $query->paginate($perPage);

            // Transform data to match frontend expectations
            $transformedData = $students->getCollection()->map(function ($student) {
                $parent = $student->parents->first();
                return [
                    'id' => $student->id,
                    'matricule' => $student->matricule,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'date_of_birth' => $student->date_of_birth,
                    'place_of_birth' => $student->place_of_birth,
                    'gender' => $student->gender,
                    'address' => $student->address,
                    'photo' => $student->photo_path,
                    'status' => $student->status ?? 'active',
                    'current_enrollment' => $student->currentEnrollment ? [
                        'class_room' => [
                            'name' => $student->currentEnrollment->classroom->name ?? 'Non inscrit'
                        ]
                    ] : null,
                    'parents' => $parent ? [[
                        'first_name' => $parent->first_name,
                        'last_name' => $parent->last_name,
                        'phone' => $parent->phone,
                        'email' => $parent->email,
                    ]] : [],
                ];
            });

            return $this->paginatedResponse($students, 'Élèves récupérés avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur liste élèves: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel élève
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'date_of_birth' => 'required|date',
                'place_of_birth' => 'nullable|string|max:100',
                'gender' => 'required|in:M,F',
                'address' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:5',
                'photo_path' => 'nullable|string|max:255',
                'user_id' => 'nullable|exists:users,id',
            ]);

            // Générer le matricule
            $year = date('Y');
            $lastStudent = Student::where('matricule', 'like', "STD-{$year}-%")
                ->orderBy('matricule', 'desc')
                ->first();

            if ($lastStudent) {
                $lastNumber = intval(substr($lastStudent->matricule, -4));
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            $validated['matricule'] = "STD-{$year}-{$newNumber}";

            $student = Student::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Élève créé avec succès',
                'data' => $student
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un élève
     */
    public function show($id)
    {
        try {
            $student = Student::with('enrollments')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $student
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur affichage élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un élève
     */
    public function update(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:100',
                'last_name' => 'sometimes|string|max:100',
                'date_of_birth' => 'sometimes|date',
                'place_of_birth' => 'nullable|string|max:100',
                'gender' => 'sometimes|in:M,F',
                'address' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:5',
                'photo_path' => 'nullable|string|max:255',
            ]);

            $student->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Élève mis à jour avec succès',
                'data' => $student->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un élève
     */
    public function destroy($id)
    {
        try {
            $student = Student::findOrFail($id);
            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Élève supprimé avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur suppression élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des élèves
     */
    public function stats()
    {
        try {
            $total = Student::count();
            $byGender = Student::select('gender', DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->pluck('count', 'gender');

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'by_gender' => $byGender,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur stats élèves: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}
