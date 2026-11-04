<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\BusinessHour;
use App\Models\Message;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\Payments\PaymentGateway::class, \App\Services\Payments\TestGateway::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.salon');

        // What each role may do. Stylists only ever see their own work.
        Gate::define('manage-business', fn (User $u) => $u->hasRole(Role::Admin, Role::Manager));
        Gate::define('manage-settings', fn (User $u) => $u->hasRole(Role::Admin));
        Gate::define('manage-users', fn (User $u) => $u->hasRole(Role::Admin));
        Gate::define('view-reports', fn (User $u) => $u->hasRole(Role::Admin, Role::Manager));

        View::composer(['layouts.site', 'site.contact'], function ($view) {
            $view->with('footerHours', BusinessHour::query()->orderByRaw('CASE weekday WHEN 0 THEN 7 ELSE weekday END')->get());
        });

        View::composer('layouts.admin', function ($view) {
            $user = auth()->user();
            if (! $user?->hasRole(Role::Admin, Role::Manager)) {
                return;
            }
            $since = $user->notifications_seen_at ?? now()->subHours(8);
            $view->with([
                'bellItems' => Activity::query()->latest('created_at')->limit(8)->get(),
                'bellUnread' => Activity::query()->where('created_at', '>', $since)->count(),
                'unreadMessages' => Message::query()->whereNull('read_at')->count(),
            ]);
        });
    }
}
