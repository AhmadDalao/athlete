<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthTokenDestroyController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return response()->json([
                'message' => 'This request was not authenticated with a personal access token.',
            ], 400);
        }

        $token->delete();

        return response()->json([], 204);
    }
}
