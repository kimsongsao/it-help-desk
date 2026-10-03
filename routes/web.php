<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\JoinOrganizationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Platform\OrganizationController as PlatformOrganizationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SlaPolicyController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketEscalationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/join/{organization}', [JoinOrganizationController::class, 'create'])->name('join');
    Route::post('/join/{organization}', [JoinOrganizationController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->middleware('throttle:10,1')->name('social.callback');
});

Route::middleware(['auth', 'active', 'tenant'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('tickets', TicketController::class)->except(['edit']);
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::post('/tickets/{ticket}/escalations', [TicketEscalationController::class, 'store'])->name('tickets.escalations.store');

    Route::get('/attachments/{attachment}', [TicketAttachmentController::class, 'show'])->name('attachments.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::delete('/profile/social-accounts/{socialAccount}', [ProfileController::class, 'destroySocialAccount'])->name('profile.social-accounts.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::middleware('can:api.access')->group(function () {
        Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
    });

    Route::middleware('can:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::resource('sla-policies', SlaPolicyController::class)->except(['show'])->middleware('can:sla.manage');
    Route::resource('workflows', WorkflowController::class)->except(['show'])->middleware('can:workflows.manage');
    Route::resource('categories', CategoryController::class)->except(['show'])->middleware('can:categories.manage');
    Route::resource('users', UserController::class)->except(['show', 'destroy'])->middleware('can:users.manage');
    Route::resource('roles', RoleController::class)->except(['show'])->middleware('can:roles.manage');

    Route::middleware('can:organization.manage')->group(function () {
        Route::get('/organization', [OrganizationSettingsController::class, 'edit'])->name('organization.edit');
        Route::put('/organization', [OrganizationSettingsController::class, 'update'])->name('organization.update');
    });

    Route::prefix('platform')->name('platform.')->middleware('can:platform.manage')->group(function () {
        Route::resource('organizations', PlatformOrganizationController::class)->except(['show', 'destroy']);
    });
});
