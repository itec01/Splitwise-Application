<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Group;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {
    }

    /**
     * Create expense.
     */
    public function store(
        CreateExpenseRequest $request,
        string $group
    ): JsonResponse {
        $groupModel = Group::find($group);

        if (!$groupModel) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found.',
                'errors' => null,
            ], 404);
        }

        $expense = $this->expenseService->createExpense(
            $groupModel,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully.',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    /**
     * List group expenses.
     */
    public function index(
        Request $request,
        string $group
    ): JsonResponse {
        $groupModel = Group::find($group);

        if (!$groupModel) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found.',
                'errors' => null,
            ], 404);
        }

        $expenses = $this->expenseService->getGroupExpenses(
            $groupModel,
            $request->user(),
            $request->only([
                'payer',
                'split_type',
                'date_from',
                'date_to',
                'per_page',
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Expenses retrieved successfully.',
            'data' => ExpenseResource::collection($expenses),
        ]);
    }

    /**
     * Show expense.
     */
    public function show(
        Request $request,
        string $expense
    ): JsonResponse {
        $expenseModel = $this->expenseService->findExpense(
            $expense,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Expense retrieved successfully.',
            'data' => new ExpenseResource($expenseModel),
        ]);
    }

    /**
     * Update expense.
     */
    public function update(
        UpdateExpenseRequest $request,
        string $expense
    ): JsonResponse {
        $expenseModel = Expense::find($expense);

        if (!$expenseModel) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
                'errors' => null,
            ], 404);
        }

        $expenseModel = $this->expenseService->updateExpense(
            $expenseModel,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data' => new ExpenseResource($expenseModel),
        ]);
    }

    /**
     * Delete expense.
     */
    public function destroy(
        Request $request,
        string $expense
    ): JsonResponse {
        $expenseModel = Expense::find($expense);

        if (!$expenseModel) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
                'errors' => null,
            ], 404);
        }

        $this->expenseService->deleteExpense(
            $expenseModel,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
            'data' => null,
        ]);
    }
}