<?php

use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->group(function () {
    // Expenses
    Route::post('/groups/{group}/expenses', [ExpenseController::class, 'store'])->middleware('group.access:member');
    Route::get('/groups/{group}/expenses', [ExpenseController::class, 'index'])->middleware('group.access:member');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->middleware('expense.access:view');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->middleware('expense.access:update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('expense.access:delete');
});
