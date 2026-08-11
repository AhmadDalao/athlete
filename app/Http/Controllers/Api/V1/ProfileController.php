<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ThemePreferenceRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function theme(ThemePreferenceRequest $request): JsonResponse
    {
        $request->user()->forceFill(['theme_preference' => (string) $request->string('theme')])->save();

        return response()->json(['data' => ['user' => UserResource::make($request->user()->fresh())]]);
    }
}
