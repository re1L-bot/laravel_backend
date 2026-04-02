<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'required|email|unique:users',
            'phone'    => 'required|string|max:20',
            'address'  => 'required|string|max:500',
            'birthdate'=> 'required|date',
            'age'      => 'required|integer|min:1|max:120',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name'           => $request->name,
            'username'       => $request->username,
            'email'          => $request->email,
            'phone_number'   => $request->phone,
            'address'        => $request->address,
            'birthday'       => $request->birthdate,
            'age'            => $request->age,
            'password'       => Hash::make($request->password),
            'email_verified_at' => now(),   // ← User is immediately verified
        ]);

        return response()->json([
            'message' => 'Account created successfully!',
        ], 201);
    }
}