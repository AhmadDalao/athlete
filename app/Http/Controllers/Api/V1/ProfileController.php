<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfileUpdateRequest;
use App\Http\Requests\Api\V1\ThemePreferenceRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use RespondsWithApi;

    public function show(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()->load(['athleteProfiles', 'coachProfiles'])));
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('theme', $data)) {
            $data['theme_preference'] = $data['theme'];
            unset($data['theme']);
        }
        $request->user()->fill($data)->save();

        return $this->success(new UserResource($request->user()->fresh()));
    }

    public function theme(ThemePreferenceRequest $request): JsonResponse
    {
        $request->user()->forceFill(['theme_preference' => (string) $request->string('theme')])->save();

        return response()->json([
            'data' => ['user' => UserResource::make($request->user()->fresh())],
            'meta' => (object) [],
            'links' => (object) [],
        ]);
    }
}
