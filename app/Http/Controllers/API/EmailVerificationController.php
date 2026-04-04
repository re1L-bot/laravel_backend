<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationController extends Controller
{
    // POST /api/email/send  — called automatically after register
    public function send(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = strtolower($request->email);

        // Rate limit: max 3 sends per email per 10 minutes
        $key = 'email-verify:' . $email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many attempts. Try again in {$seconds} seconds.",
            ], 429);
        }
        RateLimiter::hit($key, 600);

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified.'], 409);
        }

        // Delete old codes and issue a fresh one
        EmailVerificationCode::where('email', $email)->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'email'      => $email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(2),
            'used'       => false,
        ]);

        Mail::to($email)->send(new EmailVerificationMail($code));

        return response()->json(['message' => 'Verification code sent.']);
    }

    // POST /api/email/verify
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'digits:6'],
        ]);

        $email = strtolower($request->email);

        $record = EmailVerificationCode::where('email', $email)
            ->where('code', $request->code)
            ->latest()
            ->first();

        if (!$record || !$record->isValid()) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Mark code as used
        $record->update(['used' => true]);
        RateLimiter::clear('email-verify:' . $email);

        return response()->json(['message' => 'Email verified successfully.']);
    }

    // POST /api/email/resend
    public function resend(Request $request): JsonResponse
    {
        // Reuse the same send logic
        return $this->send($request);
    }
}