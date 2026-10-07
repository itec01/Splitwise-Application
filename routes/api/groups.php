<?php

use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->group(function () {
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

    // Group balances
    Route::get('/groups/{group}/balances', [GroupController::class, 'balances'])->middleware('group.access:member');
});
