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
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);

    // Group members
    Route::get('/groups/{group}/members', [GroupController::class, 'members']);
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
    Route::delete(
        '/groups/{group}/members/{user}',
        [GroupController::class, 'removeMember']
    );

    // Expenses
    Route::post('/groups/{group}/expenses', [ExpenseController::class, 'store']);
    Route::get('/groups/{group}/expenses', [ExpenseController::class, 'index']);
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

    // Group balances
    Route::get('/groups/{group}/balances', [GroupController::class, 'balances']);

    // Settlements
    Route::post(
        '/groups/{group}/settlements',
        [SettlementController::class, 'store']
    );
    Route::get(
        '/groups/{group}/settlements',
        [SettlementController::class, 'index']
    );
    Route::put(
        '/groups/{group}/settlements/{settlement}',
        [SettlementController::class, 'update']
    );
    Route::delete(
        '/groups/{group}/settlements/{settlement}',
        [SettlementController::class, 'destroy']
    );
});