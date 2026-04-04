<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
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

    public function register(Request $request): JsonResponse
    {
        try {
            Log::info('Registration attempt', ['email' => $request->email]);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'security_question' => 'required|string|max:500',
                'security_answer' => 'required|string|min:2|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Hash the security answer for storage
            $hashedAnswer = Hash::make($request->security_answer);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'security_question' => $request->security_question,
                'security_answer' => $hashedAnswer,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User registered successfully', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Registration successful',
                'user' => $user,
                'token' => $token,
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
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
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
                'new_password_confirmation' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                    'errors' => [
                        'current_password' => ['Current password is incorrect.']
                    ]
                ], 422);
            }

            // Check new password is not the same as current
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'message' => 'New password must be different from the current password.',
                    'errors' => [
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

    // ============================================
    // SECURITY QUESTION METHODS FOR PASSWORD RESET
    // ============================================
    
    /**
     * Get security question for a user by email
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
            
            if (!$user->security_question) {
                return response()->json([
                    'message' => 'No security question set for this account. Please contact support.'
                ], 404);
            }

            return response()->json([
                'question' => $user->security_question
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get security question error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify security answer and generate reset token
     */
    public function verifySecurityAnswer(Request $request): JsonResponse
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

            if (!$user->security_answer) {
                return response()->json([
                    'message' => 'No security answer set for this account'
                ], 404);
            }

            if (!Hash::check($request->answer, $user->security_answer)) {
                Log::warning('Failed security answer attempt', ['email' => $request->email]);
                return response()->json([
                    'message' => 'Incorrect answer. Please try again.'
                ], 401);
            }

            // Generate a reset token
            $resetToken = Str::random(60);
            
            // Store token temporarily in cache (expires in 1 hour)
            cache()->put('password_reset_' . $resetToken, $user->email, 3600);

            Log::info('Security answer verified', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Answer verified successfully',
                'reset_token' => $resetToken
            ]);
            
        } catch (\Exception $e) {
            Log::error('Verify security answer error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using security question verification
     */
    public function resetPasswordWithSecurity(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'reset_token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify reset token
            $cacheKey = 'password_reset_' . $request->reset_token;
            $cachedEmail = cache($cacheKey);
            
            if (!$cachedEmail || $cachedEmail !== $request->email) {
                return response()->json([
                    'message' => 'Invalid or expired reset token. Please start over.'
                ], 401);
            }

            $user = User::where('email', $request->email)->first();
            
            // Check if new password is same as old password
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'New password must be different from the current password.',
                    'errors' => [
                        'password' => ['New password must be different from the current password.']
                    ]
                ], 422);
            }
            
            // Update password
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Delete the reset token from cache
            cache()->forget($cacheKey);
            
            // Revoke all tokens to force logout from all devices
            $user->tokens()->delete();

            Log::info('Password reset successfully via security question', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Password reset successfully. Please login with your new password.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optional: Update security question for authenticated user
     */
    public function updateSecurityQuestion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'security_question' => 'required|string|max:500',
                'security_answer' => 'required|string|min:2|max:255',
                'password' => 'required|string', // Require password confirmation for security
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Password is incorrect',
                    'errors' => [
                        'password' => ['Current password is incorrect.']
                    ]
                ], 422);
            }

            // Update security question and answer
            $user->update([
                'security_question' => $request->security_question,
                'security_answer' => Hash::make($request->security_answer),
            ]);

            Log::info('Security question updated', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Security question updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Update security question error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}