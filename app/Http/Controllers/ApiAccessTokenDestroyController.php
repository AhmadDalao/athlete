<?php

namespace App\Http\Controllers;

use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiAccessTokenDestroyController extends Controller
{
    public function __invoke(Request $request, int $tokenId, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->firstOrFail();
        $tokenName = $token->name;
        $token->delete();

        $auditLogger->record(
            $request,
            'api_token.revoked',
            $request->user(),
            "{$request->user()->name} revoked API token {$tokenName}.",
            [
                'token_id' => $tokenId,
                'token_name' => $tokenName,
            ],
        );

        return back()->with('success', 'API token revoked.');
    }
}
