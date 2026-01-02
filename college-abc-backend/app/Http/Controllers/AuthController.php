<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Modules\Student\Entities\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'identifier' => 'required', // Peut être email ou matricule
                'password' => 'required',
            ]);

            $user = null;
            $authenticatedViaMatricule = false;

            // 1. D'abord, essayer de trouver un élève par matricule
            $student = Student::where('matricule', $credentials['identifier'])->first();

            if ($student) {
                // L'identifiant est un matricule - chercher le PARENT de cet élève
                $parent = $student->parents()->first();

                if ($parent) {
                    $user = $parent; // Le parent est un User
                    $authenticatedViaMatricule = true;
                    Log::info('Parent login via matricule', [
                        'matricule' => $credentials['identifier'],
                        'student' => $student->first_name . ' ' . $student->last_name,
                        'parent_id' => $user->id
                    ]);
                } else {
                    // Pas de parent trouvé - peut-être que user_id de student est le parent
                    if ($student->user_id) {
                        $user = User::find($student->user_id);
                        $authenticatedViaMatricule = true;
                    }
                }
            }

            // 2. Si pas trouvé par matricule, chercher par email (admin/directeur/enseignant)
            if (!$user) {
                $user = User::where('email', $credentials['identifier'])
                    ->whereIn('role', ['admin', 'super_admin', 'director', 'teacher'])
                    ->first();
            }

            // 3. Vérification du mot de passe
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'message' => 'Matricule ou mot de passe incorrect.'
                ], 401);
            }

            // 4. Vérifier si c'est le mot de passe par défaut
            $isDefaultPassword = false;
            $defaultPassword = config('auth.default_password', 'Abc12345!');
            if (Hash::check($defaultPassword, $user->password)) {
                $isDefaultPassword = true;
            }

            // 5. Création du token
            $token = $user->createToken('auth_token')->plainTextToken;

            // 6. Récupération des enfants si c'est un parent
            $children = [];

            // Determine role safely
            $role = $user->role;

            if ($authenticatedViaMatricule || $role === 'parent' || $user->hasRole('parent')) {
                $role = 'parent';
            }

            if ($role === 'parent') {
                // Trouver tous les enfants liés à ce parent via la table pivot
                $children = Student::whereHas('parents', function ($query) use ($user) {
                    $query->where('parent_id', $user->id);
                })
                    ->with(['enrollments.classroom.level'])
                    ->get()
                    ->map(function ($student) {
                        $latestEnrollment = $student->enrollments->sortByDesc('created_at')->first();
                        $className = $latestEnrollment && $latestEnrollment->classroom
                            ? $latestEnrollment->classroom->name
                            : 'Non inscrit';

                        return [
                            'id' => $student->id,
                            'firstName' => $student->first_name,
                            'lastName' => $student->last_name,
                            'matricule' => $student->matricule,
                            'class' => $className,
                            'photo' => $student->photo
                        ];
                    });
            }

            // Final role fallback if empty (e.g. for teacher/admin)
            if (empty($role)) {
                $role = $user->getRoleNames()->first();
            }

            Log::info('Login success', ['email' => $user->email, 'role' => $role]);

            return response()->json([
                'token' => $token,
                'must_change_password' => $isDefaultPassword,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $role,
                    'email' => $user->email,
                    'children' => $children
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Login error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Erreur serveur lors de la connexion.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.'
        ]);

        $user = $request->user();
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }
}
