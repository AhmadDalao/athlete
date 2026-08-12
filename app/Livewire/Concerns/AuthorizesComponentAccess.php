<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

trait AuthorizesComponentAccess
{
    public function bootAuthorizesComponentAccess(): void
    {
        $actor = Auth::user();

        abort_unless($actor && collect($this->componentPermissions())->contains(
            fn (string $permission): bool => $actor->can($permission)
        ), 403);
    }

    /** @return list<string> */
    abstract protected function componentPermissions(): array;
}
