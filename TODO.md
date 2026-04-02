# Fix Registration JSON Error - TODO
Status: 🔄 In Progress

## Step 1: ✅ Remove duplicate route conflict in routes/api.php
- Comment out RegisterController::register route
- Keep AuthController::register (simple version)

## Step 2: ✅ Fix User model fillable fields
- Add first_name, last_name, username, phone, address, birthdate, age

## Step 3: [PENDING] Create missing EmailVerificationCode model + migration (for future OTP)

## Step 4: [PENDING] Update config/services.php (recaptcha)

## Step 5: [PENDING] Run migrations + clear caches

## Step 6: [PENDING] Test /api/register endpoint

## Step 7: [COMPLETE] ✅ Initial setup analysis done
