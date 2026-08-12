<?php

namespace App\Providers;

use App\Http\Middleware\ResolveOrganization;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\OrganizationContext;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(OrganizationContext::class, fn (): OrganizationContext => new OrganizationContext);
    }

    public function boot(): void
    {
        Livewire::addPersistentMiddleware(ResolveOrganization::class);

        Gate::before(fn (User $user): ?bool => $user->isPlatformOwner() ? true : null);

        foreach (PermissionCatalog::all() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }

        View::composer('*', function ($view): void {
            $view->with('platformSettings', PlatformSetting::publicMap());
        });
    }
}
