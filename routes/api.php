<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\PlaceController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES (No authentication required)
// ============================================

// Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/cleanup-unverified', [RegisterController::class, 'cleanupUnverified']);
// Registration Routes (Using RegisterController with reCAPTCHA)
Route::post('/register', [RegisterController::class, 'register']);

// Test route to check if API is working
Route::get('/test', function() {
    return response()->json([
        'message' => '✅ API is working!',
        'timestamp' => now(),
        'status' => 'success'
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
Route::get('/test', function() {
    return response()->json(['message' => 'Backend is working!']);
});

Route::get('/migrate', function() {
    \Artisan::call('migrate', ['--force' => true]);
    return response()->json(['message' => 'Migrations completed.']);
});

