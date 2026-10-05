<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth.token')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);


    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);

    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | Group Members
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/groups/{group}/members',
        [GroupController::class, 'members']
    );

    Route::post(
        '/groups/{group}/members',
        [GroupController::class, 'addMember']
    );

    Route::delete(
        '/groups/{group}/members/{user}',
        [GroupController::class, 'removeMember']
    );


    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */

    // Create expense
    Route::post(
        '/groups/{group}/expenses',
        [ExpenseController::class, 'store']
    );

    // List group expenses
    Route::get(
        '/groups/{group}/expenses',
        [ExpenseController::class, 'index']
    );

    // Show expense
    Route::get(
        '/expenses/{expense}',
        [ExpenseController::class, 'show']
    );

    // Update expense
    Route::put(
        '/expenses/{expense}',
        [ExpenseController::class, 'update']
    );

    // Delete expense
    Route::delete(
        '/expenses/{expense}',
        [ExpenseController::class, 'destroy']
    );
});