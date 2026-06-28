<?php

namespace App\Http\Controllers;

use App\Services\Whoop\WhoopWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;
use UnexpectedValueException;

class WhoopWebhookController extends Controller
{
    public function __invoke(Request $request, WhoopWebhookService $webhooks): JsonResponse
    {
        try {
            $result = $webhooks->handleWebhook(
                $request->getContent(),
                $request->header('X-WHOOP-Signature'),
                $request->header('X-WHOOP-Signature-Timestamp'),
            );
        } catch (JsonException|UnexpectedValueException|RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'WHOOP webhook payload is invalid.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'received' => true,
            'queued' => $result['queued'],
            'duplicate' => $result['duplicate'],
        ]);
    }
}
