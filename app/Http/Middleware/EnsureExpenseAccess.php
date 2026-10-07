<?php

namespace App\Http\Middleware;

use App\Models\Expense;
use App\Models\Group;
use Closure;
use Illuminate\Http\Request;

class EnsureExpenseAccess
{
    public function handle(Request $request, Closure $next, string $ability = 'view')
    {
        $expenseId = $request->route('expense');

        if (! $expenseId) {
            return $this->errorNotFound();
        }

        $expense = Expense::find($expenseId);

        if (! $expense) {
            return $this->errorNotFound();
        }

        $routeGroupId = $request->route('group');
        if ($routeGroupId && (string) $expense->group_id !== $routeGroupId) {
            return $this->errorNotFound();
        }

        $group = $request->attributes->get('group') ?? Group::find($expense->group_id);

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
        $payerId = (string) $expense->paid_by;
        $ownerId = (string) $group->owner_id;

        // Ensure user is member of the group
        if (! in_array($userId, $group->member_ids ?? [], true)) {
            return $this->errorForbidden();
        }

        if ($ability === 'update') {
            if ($ownerId !== $userId && $payerId !== $userId) {
                return $this->errorForbidden();
            }
        } elseif ($ability === 'delete') {
            if ($payerId !== $userId) {
                return $this->errorForbidden();
            }
        }

        // Attach models to the request
        $request->attributes->set('expense', $expense);
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
