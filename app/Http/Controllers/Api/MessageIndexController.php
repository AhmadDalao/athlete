<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsMobileAppPayloads;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageIndexController extends Controller
{
    use BuildsMobileAppPayloads;
    use FormatsApiPayloads;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $this->abortUnlessMobileUser($user);

        return response()->json([
            'data' => [
                'viewer' => $this->viewerPayload($user),
                'threads' => $this->visibleMessageThreads($user, markRead: true),
            ],
            'meta' => $this->metaPayload(),
        ]);
    }
}
