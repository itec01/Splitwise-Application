<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthenticationService $authenticationService
    ) {}

    /**
     * Register
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $result = $this->authenticationService->register($validated);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                // 'token' => $result['token'],
            ],
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authenticationService->login($validated);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => new UserResource($request->user()),
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $token = $request->header('Authorization');

        $this->authenticationService->logout($token);

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => null,
        ], 200);
    }
}
