<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * UserController - Gestion des utilisateurs
 * 
 * CRUD complet pour les utilisateurs du système
 */
class UserController extends Controller
{
    /**
     * Liste des utilisateurs avec filtres
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // Filtres
            if ($request->has('role') && $request->role) {
                $query->where('role', $request->role);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur liste utilisateurs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:8',
                'role' => ['required', Rule::in(['super_admin', 'direction', 'admin', 'secretariat', 'comptabilite', 'enseignant', 'parent', 'eleve'])],
                'matricule' => 'nullable|string|unique:users,matricule',
                'is_active' => 'boolean',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['is_active'] = $validated['is_active'] ?? true;

            $user = User::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $user
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un utilisateur
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur affichage utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:100',
                'last_name' => 'sometimes|string|max:100',
                'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
                'phone' => 'nullable|string|max:20',
                'password' => 'sometimes|string|min:8',
                'role' => ['sometimes', Rule::in(['super_admin', 'direction', 'admin', 'secretariat', 'comptabilite', 'enseignant', 'parent', 'eleve'])],
                'matricule' => ['sometimes', 'nullable', 'string', Rule::unique('users')->ignore($id)],
                'is_active' => 'sometimes|boolean',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $user->fresh()
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
                'message' => 'Utilisateur non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Ne pas supprimer son propre compte
            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur suppression utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des utilisateurs par rôle
     */
    public function stats()
    {
        try {
            $stats = User::select('role', DB::raw('count(*) as count'))
                ->where('is_active', true)
                ->groupBy('role')
                ->pluck('count', 'role');

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur stats utilisateurs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}
