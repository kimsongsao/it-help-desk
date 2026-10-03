<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TicketActivityController;
use App\Http\Controllers\Api\V1\TicketCommentController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketEscalationController;
use App\Http\Controllers\TicketAttachmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:6,1')->name('auth.token.store');

    Route::middleware(['auth:sanctum', 'active', 'tenant', 'can:api.access', 'throttle:120,1'])->group(function () {
        Route::get('/me', [AuthTokenController::class, 'show'])->name('me');
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::apiResource('tickets', TicketController::class);
        Route::get('/tickets/{ticket}/comments', [TicketCommentController::class, 'index'])->name('tickets.comments.index');
        Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
        Route::post('/tickets/{ticket}/escalations', [TicketEscalationController::class, 'store'])->name('tickets.escalations.store');
        Route::get('/tickets/{ticket}/activities', [TicketActivityController::class, 'index'])->name('tickets.activities.index');

        Route::get('/attachments/{attachment}', [TicketAttachmentController::class, 'show'])->name('attachments.show');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/teams', [TeamController::class, 'index'])->middleware('feature:teams')->name('teams.index');
        Route::get('/reports/summary', [ReportController::class, 'show'])->middleware('can:reports.view')->name('reports.summary');
    });
});
