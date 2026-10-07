<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->group(function () {
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

    // Attachments Route
    Route::post(
        '/settlements/{settlementId}/attachments',
        [AttachmentController::class, 'store']
    );
    Route::get(
        '/attachments/{attachmentId}/download',
        [AttachmentController::class, 'download']
    );
});
