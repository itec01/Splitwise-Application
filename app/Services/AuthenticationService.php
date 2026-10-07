<?php

namespace App\Services;

use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        // Check if email already exists
        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Generate random API token

        return [
            'user' => $user,

        ];
    }

    /**
     * Login user.
     */
    public function login(array $data): array
    {
        // Find user by email
        $user = User::where('email', $data['email'])->first();

        // Invalid credentials
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate a new random token
        $token = bin2hex(random_bytes(32));

        // Store token
        Token::create([
            'user_id' => $user->_id,
            'api_token' => $token,
            'expires_at' => now()->addHours(2), // Optional: Set an expiration time for the token
        ]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Get authenticated user from API token.
     */
    public function getUserByToken(string $token): ?User
    {
        $tokenRecord = Token::where('api_token', $token)->first();

        if (! $tokenRecord) {
            return null;
        }

        return User::find($tokenRecord->user_id);
    }

    /**
     * Logout user by deleting the current token.
     */
    public function logout(string $token): bool
    {
        $tokenRecord = Token::where('api_token', $token)->first();

        if (! $tokenRecord) {
            return false;
        }

        return (bool) $tokenRecord->delete();
    }
}
