<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\StripeBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeBillingService $billing): JsonResponse
    {
        try {
            $result = $billing->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (UnexpectedValueException|SignatureVerificationException|RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        return response()->json([
            'received' => true,
            'processed' => $result['processed'],
        ]);
    }
}
