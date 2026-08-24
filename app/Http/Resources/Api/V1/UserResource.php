<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $membership = $this->activeOrganizationMembership();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'primary_goal' => $this->primary_goal,
            'platform_role' => $this->role,
            'organization_role' => $membership?->role,
            'permissions' => $this->effectivePermissions(),
            'current_organization_id' => $this->current_organization_id,
            'theme_preference' => $this->theme_preference,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'landing_path' => $this->landingPath(),
        ];
    }
}
