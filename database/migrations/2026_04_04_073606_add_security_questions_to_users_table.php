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
     * Register a new user with security question
     */
    public function register(Request $request): JsonResponse
    {
        try {
            Log::info('Registration attempt', ['email' => $request->email]);
            
            // Validate user input with security question
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'birthdate' => 'required|date',
                'age' => 'required|integer|min:1|max:120',
                'password' => 'required|string|min:8|confirmed',
                'security_question' => 'required|string|max:255',
                'security_answer' => 'required|string|min:2|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Create user with security question
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'birthdate' => $request->birthdate,
                'age' => $request->age,
                'password' => Hash::make($request->password),
                'security_question' => $request->security_question,
                'security_answer' => Hash::make($request->security_answer), // Store answer hashed
            ]);
            
            Log::info('User registered successfully', ['user_id' => $user->id, 'email' => $user->email]);
            
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

    /**
     * Verify security question for password reset
     */
    public function verifySecurityQuestion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'answer' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->answer, $user->security_answer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect answer to security question'
                ], 401);
            }

            // Generate a temporary token for password reset
            $resetToken = bin2hex(random_bytes(32));
            
            // Store in cache or database (simplified - use cache for demo)
            cache(["password_reset_{$user->id}" => $resetToken], now()->addMinutes(15));

            return response()->json([
                'success' => true,
                'message' => 'Security question verified successfully',
                'reset_token' => $resetToken,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Security question verification error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's security question (for password reset)
     */
    public function getSecurityQuestion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'success' => true,
                'question' => $user->security_question
            ]);

        } catch (\Exception $e) {
            Log::error('Get security question error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ... keep your existing login, logout, etc. methods
}