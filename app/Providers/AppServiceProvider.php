<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Support\DisplayTimezone;
use App\Support\Tenancy;
use Carbon\CarbonInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
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
        $this->app->scoped(DisplayTimezone::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        /*
         * Convert a stored UTC date to the time zone of the person viewing it: $ticket->created_at->forViewer().
         * Carbon macros are shared by mutable and immutable dates, so the copy keeps the original's type.
         */
        Carbon::macro('forViewer', fn (): CarbonInterface => $this->copy()->setTimezone(app(DisplayTimezone::class)->get()));

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
