<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image
        ]);

        // Handle image upload
        $imagePath = $request->file('image')->store('images', 'public');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imagePath, // Save image path in DB
        ]);
        // Generate a token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user,
            'token' => $token,
            'image' => url('storage/' . $user->image) // ✅ Prepend URL here
        ], 201);
    }

    // Login
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

        // Revoke old tokens before creating a new one
        $user->tokens()->delete();

        // Generate new token
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful!',
            'token' => $token,
            'user' => $user, // Optional: Send user data if needed
        ], 200);
    }

    // Get logged-in user
    public function user(Request $request)
    {
        return response()->json($request->user());

    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully!'
        ]);
    }

    public function deleteAccount(Request $request)
    {

        Log::info('Delete account request received.', ['user' => Auth::user()]);

        $user = Auth::user(); // Get the logged-in user

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Delete user from the database
        $user->tokens()->delete(); // Revoke all tokens
        $user->delete();
        Log::info('User deleted successfully.');

        return response()->json(['message' => 'Account deleted successfully'], 200);
    }

}
