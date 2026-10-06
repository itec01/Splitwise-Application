<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Settlement;
use App\Models\Group;

class EnsureSettlementAccess
{
    public function handle(Request $request, Closure $next, string $ability = 'view')
    {
        $settlementId = $request->route('settlement');
        
        if (!$settlementId) {
            return $this->errorNotFound();
        }

        $settlement = Settlement::find($settlementId);

        if (!$settlement) {
            return $this->errorNotFound();
        }

        $routeGroupId = $request->route('group');
        if ($routeGroupId && (string) $settlement->group_id !== $routeGroupId) {
            return $this->errorNotFound();
        }

        $group = $request->attributes->get('group') ?? Group::find($settlement->group_id);

        if (!$group) {
            return $this->errorNotFound();
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $userId = (string) $user->getKey();
        $payerId = (string) $settlement->paid_by;

        // Ensure user is member
        if (!in_array($userId, $group->member_ids ?? [], true)) {
            return $this->errorForbidden();
        }

        if ($ability === 'update' || $ability === 'delete') {
            if ($payerId !== $userId) {
                return $this->errorForbidden();
            }
        }

        $request->attributes->set('settlement', $settlement);
        $request->attributes->set('group', $group);

        return $next($request);
    }

    protected function errorNotFound()
    {
        return response()->json([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    protected function errorForbidden()
    {
        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to perform this action.'
        ], 403);
    }
}
