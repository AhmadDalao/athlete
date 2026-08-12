<?php

namespace App\Livewire\Admin;

class CoachesTable extends UsersTable
{
    public string $role = 'coach';

    public string $newRole = 'coach';

    public bool $roleLocked = true;

    protected function constrainedRole(): ?string
    {
        return 'coach';
    }
}
