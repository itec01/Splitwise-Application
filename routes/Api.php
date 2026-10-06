<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SettlementController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth.token')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Groups
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{group}', [GroupController::class, 'show'])->middleware('group.access:member');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->middleware('group.access:owner');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->middleware('group.access:owner');

    // Group members
    Route::get('/groups/{group}/members', [GroupController::class, 'members'])->middleware('group.access:member');
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->middleware('group.access:owner');
    Route::delete(
        '/groups/{group}/members/{user}',
        [GroupController::class, 'removeMember']
    )->middleware('group.access:owner');

    // Expenses
    Route::post('/groups/{group}/expenses', [ExpenseController::class, 'store'])->middleware('group.access:member');
    Route::get('/groups/{group}/expenses', [ExpenseController::class, 'index'])->middleware('group.access:member');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->middleware('expense.access:view');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->middleware('expense.access:update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('expense.access:delete');

    // Group balances
    Route::get('/groups/{group}/balances', [GroupController::class, 'balances'])->middleware('group.access:member');

    // Settlements
    Route::post(
        '/groups/{group}/settlements',
        [SettlementController::class, 'store']
    )->middleware('group.access:member');
    Route::get(
        '/groups/{group}/settlements',
        [SettlementController::class, 'index']
    )->middleware('group.access:member');
    Route::put(
        '/groups/{group}/settlements/{settlement}',
        [SettlementController::class, 'update']
    )->middleware(['group.access:member', 'settlement.access:update']);
    Route::delete(
        '/groups/{group}/settlements/{settlement}',
        [SettlementController::class, 'destroy']
    )->middleware(['group.access:member', 'settlement.access:delete']);
});