<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Cloudinary\Cloudinary;

class AuthController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        // ✅ Use single CLOUDINARY_URL from config/cloudinary.php
        $this->cloudinary = new Cloudinary(config('cloudinary.cloudinary_url'));
    }

    // ✅ Register a new user
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // 🔼 Upload profile image to Cloudinary
        $uploaded = $this->cloudinary->uploadApi()->upload($request->file('image')->getRealPath());
        $imageUrl = $uploaded['secure_url'];

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imageUrl,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user,
            'token' => $token,
            'image' => $imageUrl
        ], 201);
    }

    // ✅ Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Remove previous tokens for clean login
        $user->tokens()->delete();
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful!',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    // ✅ Get logged-in user
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    // ✅ Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully!'
        ]);
    }

    // ✅ Delete account
    public function deleteAccount(Request $request)
    {
        Log::info('Delete account request received.', ['user' => Auth::user()]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->tokens()->delete();
        $user->delete();

        Log::info('User deleted successfully.');

        return response()->json(['message' => 'Account deleted successfully'], 200);
    }
}
