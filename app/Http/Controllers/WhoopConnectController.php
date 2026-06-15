<?php

namespace App\Http\Controllers;

use App\Services\Whoop\WhoopApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class WhoopConnectController extends Controller
{
    public function __invoke(WhoopApiClient $client): RedirectResponse
    {
        abort_unless($client->isConfigured(), 503, 'WHOOP credentials are not configured yet.');

        $state = Str::uuid()->toString();

        session()->put('throughline.whoop.oauth_state', $state);

        return redirect()->away($client->authorizationUrl($state));
    }
}
