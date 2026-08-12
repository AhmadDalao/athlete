<?php

namespace App\Livewire\Admin;

class AthletesTable extends UsersTable
{
    public string $role = 'athlete';

    public string $newRole = 'athlete';

    public bool $roleLocked = true;

    protected function constrainedRole(): ?string
    {
        return 'athlete';
    }
}
