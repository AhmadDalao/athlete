<?php

namespace App\Support;

use App\Models\Organization;

class OrganizationContext
{
    private ?Organization $organization = null;

    public function set(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function id(): ?int
    {
        return $this->organization?->getKey();
    }

    public function clear(): void
    {
        $this->organization = null;
    }
}
