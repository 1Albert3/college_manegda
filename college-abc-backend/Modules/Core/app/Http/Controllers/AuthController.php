<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Core\Entities\User;
use Modules\Core\Http\Requests\LoginRequest;
use Modules\Core\Http\Requests\RegisterRequest;
use Modules\Core\Http\Requests\UpdateProfileRequest;
use Modules\Core\Http\Requests\ChangePasswordRequest;
use Modules\Core\Http\Requests\ForgotPasswordRequest;

class AuthController extends Controller
{
    /**
     * Login user and return token
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return ApiResponse::error('Your account is inactive', 403);
        }

        // Update last login
        $user->last_login_at = now();
        $user->save();

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user->load('roles'),
            'token' => $token
        ], 'Login successful');
    }

    /**
     * Register new user
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role_type' => $data['role_type'] ?? 'student',
            'is_active' => true,
        ]);

        // Assign default role if provided
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token
        ], 'Registration successful', 201);
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        $user = User::with(['roles', 'profile'])->find(Auth::id());

        return ApiResponse::success($user);
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = User::find(Auth::id());

        $data = $request->validated();

        $user->update($data);

        return ApiResponse::success($user->fresh(['roles', 'profile']), 'Profile updated successfully');
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = User::find(Auth::id());

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('Current password is incorrect', 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return ApiResponse::success(null, 'Password changed successfully');
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return ApiResponse::error('Unable to send reset link', 422);
        }

        return ApiResponse::success(null, 'Password reset link sent to your email');
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }
}
