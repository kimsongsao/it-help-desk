<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Tenancy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('components.layouts.app', function (\Illuminate\View\View $view): void {
            $user = auth()->user();

            $view->with([
                'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
                'recentNotifications' => $user?->notifications()->limit(6)->get() ?? collect(),
            ]);
        });

        Gate::define('platform.manage', fn (User $user): bool => $user->isPlatformAdmin());

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}
