<?php

namespace App\Http\Middleware;

use App\Models\Token;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /*
         * Get token from:
         *
         * Authorization: Bearer RANDOM_TOKEN
         */
        $token = $request->header('Authorization');

        // Token is missing
        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token is required.',
                'errors' => null,
            ], 401);
        }

        // Find token in tokens collection
        $tokenRecord = Token::where('api_token', $token)->first();

        // Token does not exist
        if (! $tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired authentication token.',
                'errors' => null,
            ], 401);
        }

        // Find user associated with token
        $user = User::find($tokenRecord->user_id);

        // User no longer exists
        if (! $user) {
            // Remove invalid token
            $tokenRecord->delete();

            return response()->json([
                'success' => false,
                'message' => 'Authenticated user not found.',
                'errors' => null,
            ], 401);
        }

        /*
         * Attach authenticated user to request.
         *
         * Controllers can now use:
         *
         * $request->user()
         */
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Continue request
        return $next($request);
    }
}
