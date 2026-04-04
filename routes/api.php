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
Route::post('/cleanup-unverified', [RegisterController::class, 'cleanupUnverified']);
// Registration Routes (Using RegisterController with reCAPTCHA)
Route::post('/register', [RegisterController::class, 'register']);

// ============================================
// reCAPTCHA VERIFICATION ROUTE (NEW)
// ============================================
Route::post('/verify-captcha', function (Request $request) {
    // Log the incoming request
    \Log::info('reCAPTCHA verification request received', [
        'has_token' => !empty($request->input('token')),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent()
    ]);
    
    $token = $request->input('token');
    
    if (!$token) {
        \Log::warning('reCAPTCHA verification failed: No token provided');
        return response()->json([
            'success' => false,
            'message' => 'No reCAPTCHA token provided'
        ], 400);
    }
    
    try {
        // Verify the token with Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $token,
            'remoteip' => $request->ip()
        ]);
        
        $result = $response->json();
        
        \Log::info('Google reCAPTCHA response', [
            'success' => $result['success'] ?? false,
            'score' => $result['score'] ?? null,
            'action' => $result['action'] ?? null,
            'error_codes' => $result['error-codes'] ?? []
        ]);
        
        // Check if verification was successful and score meets threshold
        $threshold = env('RECAPTCHA_THRESHOLD', 0.5);
        
        if ($result['success'] && $result['score'] >= $threshold) {
            return response()->json([
                'success' => true,
                'score' => $result['score'],
                'message' => 'Verification successful'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'score' => $result['score'] ?? 0,
                'message' => 'Verification failed. Please try again.',
                'error_codes' => $result['error-codes'] ?? []
            ], 400);
        }
        
    } catch (\Exception $e) {
        \Log::error('reCAPTCHA verification error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Unable to verify reCAPTCHA. Please check your connection.',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('verify.captcha');

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