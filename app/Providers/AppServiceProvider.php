<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isOwner() ? true : null);

        foreach (PermissionCatalog::all() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }

        View::composer('*', function ($view): void {
            $view->with('platformSettings', PlatformSetting::publicMap());
        });
    }
}
