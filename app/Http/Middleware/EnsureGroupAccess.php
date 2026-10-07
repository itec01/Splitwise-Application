<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Http\Request;

class EnsureGroupAccess
{
    public function handle(Request $request, Closure $next, string $ability = 'member')
    {
        $groupId = $request->route('group');

        if (! $groupId) {
            return $this->errorNotFound();
        }

        $group = Group::find($groupId);

        if (! $group) {
            return $this->errorNotFound();
        }

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = (string) $user->getKey();

        if ($ability === 'owner') {
            if ((string) $group->owner_id !== $userId) {
                return $this->errorForbidden();
            }
        } else {
            // Default to 'member' check.
            // Note: owners are also members in this application (added during group creation).
            // But we should verify they are in the member_ids array.
            if (! in_array($userId, $group->member_ids ?? [], true)) {
                return $this->errorForbidden();
            }
        }

        // Attach the authorized group to the request so controllers don't need to fetch it again
        $request->attributes->set('group', $group);

        return $next($request);
    }

    protected function errorNotFound()
    {
        return response()->json([
            'success' => false,
            'message' => 'Resource not found.',
        ], 404);
    }

    protected function errorForbidden()
    {
        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to perform this action.',
        ], 403);
    }
}
