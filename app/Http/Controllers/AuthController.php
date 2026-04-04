<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            Log::info('Registration attempt', ['email' => $request->email]);
            
            // Validate user input (username removed)
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'birthdate' => 'required|date',
                'age' => 'required|integer|min:1|max:120',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Create user (username removed)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'birthdate' => $request->birthdate,
                'age' => $request->age,
                'password' => Hash::make($request->password),
            ]);
            
            Log::info('User registered successfully', ['user_id' => $user->id, 'email' => $user->email]);
            
            // Create token for auto-login after registration
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'message' => 'Registration successful',
                'user' => $user,
                'token' => $token
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            Log::info('Login attempt', ['email' => $request->email]);
            
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password'          => 'required',
                'new_password'              => 'required|min:8|confirmed',
                'new_password_confirmation' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                    'errors'  => [
                        'current_password' => ['Current password is incorrect.']
                    ]
                ], 422);
            }

            // Check new password is not the same as current
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'message' => 'New password must be different from the current password.',
                    'errors'  => [
                        'new_password' => ['New password must be different from the current password.']
                    ]
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Revoke all tokens so the user is logged out everywhere
            $user->tokens()->delete();

            Log::info('Password changed', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Password changed successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Change password error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function cleanupUnverified(Request $request): JsonResponse
    {
        try {
            $deleted = User::whereNull('email_verified_at')
                ->where('created_at', '<', now()->subDay())
                ->delete();
            
            return response()->json([
                'message' => "Cleaned up {$deleted} unverified users"
            ]);
        } catch (\Exception $e) {
            Log::error('Cleanup error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }
}