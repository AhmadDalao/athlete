<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizations = $request->user()->isPlatformAdmin()
            ? Organization::query()->where('status', 'active')->orderBy('name')->get()
            : $request->user()->organizations()->wherePivot('status', 'active')->orderBy('name')->get();

        return response()->json(['data' => OrganizationResource::collection($organizations)]);
    }

    public function select(Request $request, Organization $organization): JsonResponse
    {
        $allowed = $request->user()->isPlatformAdmin() || $request->user()->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($allowed && $organization->status === 'active', 403);

        $request->user()->forceFill(['current_organization_id' => $organization->id])->save();

        return response()->json([
            'data' => [
                'organization' => OrganizationResource::make($organization),
                'user' => UserResource::make($request->user()->fresh()),
            ],
        ]);
    }
}
