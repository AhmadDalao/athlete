<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\Billing\StripeBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CheckoutSessionStoreController extends Controller
{
    public function __invoke(Request $request, StripeBillingService $billing): Response|RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail($validated['plan_id']);

        try {
            return Inertia::location($billing->createCheckoutUrl($request->user(), $plan));
        } catch (RuntimeException $exception) {
            return back()->with('status', $exception->getMessage());
        }
    }
}
