<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtp;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // STEP 1: Send 6-digit OTP to the given email
    // POST /api/password/forgot
    // Body: { "email": "admin@example.com" }
    // ──────────────────────────────────────────────────────────────
    public function sendCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($request->email);

        // --- Rate limiting: max 3 attempts per email per 10 minutes ---
        $rateLimitKey = 'password-reset:' . $email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 600); // decay 10 minutes

        // --- Always respond with success (don't reveal if email exists) ---
        // Only send mail when the user actually exists
        $user = User::where('email', $email)->first();
        if ($user) {
            // Delete any previous unused codes for this email
            PasswordResetCode::where('email', $email)->delete();

            // Generate a cryptographically random 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            PasswordResetCode::create([
                'email'      => $email,
                'code'       => $code,
                'expires_at' => now()->addMinutes(2),
                'used'       => false,
            ]);

            Mail::to($email)->send(new PasswordResetOtp($code));
        }

        return response()->json([
            'message' => 'If that email exists, a verification code has been sent.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 2: Verify the OTP code
    // POST /api/password/verify
    // Body: { "email": "...", "code": "123456" }
    // ──────────────────────────────────────────────────────────────
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'digits:6'],
        ]);

        $record = PasswordResetCode::where('email', strtolower($request->email))
            ->where('code', $request->code)
            ->latest()
            ->first();

        if (!$record || !$record->isValid()) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        return response()->json([
            'message' => 'Code verified successfully.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 3: Reset the password
    // POST /api/password/reset
    // Body: { "email": "...", "code": "123456",
    //         "password": "...", "password_confirmation": "..." }
    // ──────────────────────────────────────────────────────────────
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $email = strtolower($request->email);

        // Re-validate the code (prevents bypassing step 2)
        $record = PasswordResetCode::where('email', $email)
            ->where('code', $request->code)
            ->latest()
            ->first();

        if (!$record || !$record->isValid()) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Mark code as used so it can't be reused
        $record->update(['used' => true]);

        // Clear rate limiter for this email
        RateLimiter::clear('password-reset:' . $email);

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }
}