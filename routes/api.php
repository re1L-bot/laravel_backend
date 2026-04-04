<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\PlaceController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// ============================================
// PUBLIC ROUTES (No authentication required)
// ============================================

// Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/cleanup-unverified', [AuthController::class, 'cleanupUnverified']);
// Registration Routes (Using RegisterController with reCAPTCHA)
Route::post('/register', [AuthController::class, 'register']);

// Test route to check if API is working
Route::get('/test', function() {
    return response()->json([
        'message' => '✅ API is working!',
        'timestamp' => now(),
        'status' => 'success',
        'captcha_endpoint' => '/verify-captcha is available'
    ]);
});

// ============================================
// PROTECTED ROUTES (Require authentication)
// ============================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',            [AuthController::class, 'user']);
    Route::post('/logout',         [AuthController::class, 'logout']);
    Route::post('/change-password',[AuthController::class, 'changePassword']);
});

// ============================================
// PLACES ROUTES
// ============================================
Route::prefix('places')->group(function () {
    Route::get('/', [PlaceController::class, 'index']);
    Route::post('/', [PlaceController::class, 'store']);
    Route::get('/{id}', [PlaceController::class, 'show']);
    Route::put('/{id}', [PlaceController::class, 'update']);
    Route::delete('/{id}', [PlaceController::class, 'destroy']);
});

// ============================================
// PASSWORD RESET ROUTES
// ============================================
Route::prefix('password')->group(function () {
    Route::post('/forgot', [PasswordResetController::class, 'sendCode']);
    Route::post('/verify', [PasswordResetController::class, 'verifyCode']);
    Route::post('/reset',  [PasswordResetController::class, 'resetPassword']);
});

// ============================================
// CORS PREFLIGHT ROUTES
// ============================================
Route::options('/{any}', function() {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
})->where('any', '.*');

// ============================================
// UTILITY ROUTES
// ============================================
Route::get('/migrate', function() {
    \Artisan::call('migrate', ['--force' => true]);
    return response()->json(['message' => 'Migrations completed.']);
});
Route::post('/password/security-question', [AuthController::class, 'getSecurityQuestion']);
Route::post('/password/verify-answer', [AuthController::class, 'verifySecurityQuestion']);
Route::post('/password/reset-with-security', [AuthController::class, 'resetPasswordWithSecurity']);
// Password reset via security question
Route::post('/password/security-question', [AuthController::class, 'getSecurityQuestion']);
Route::post('/password/verify-answer', [AuthController::class, 'verifySecurityAnswer']);
Route::post('/password/reset-with-security', [AuthController::class, 'resetPasswordWithSecurity']);