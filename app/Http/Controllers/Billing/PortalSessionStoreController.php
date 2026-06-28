<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\StripeBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PortalSessionStoreController extends Controller
{
    public function __invoke(Request $request, StripeBillingService $billing): Response|RedirectResponse
    {
        try {
            return Inertia::location($billing->createPortalUrl($request->user()));
        } catch (RuntimeException $exception) {
            return back()->with('status', $exception->getMessage());
        }
    }
}
