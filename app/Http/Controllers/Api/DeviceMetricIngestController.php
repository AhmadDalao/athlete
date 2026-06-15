<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceConnection;
use App\Services\DeviceMetricIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceMetricIngestController extends Controller
{
    public function __invoke(Request $request, DeviceConnection $deviceConnection, DeviceMetricIngestionService $ingestionService): JsonResponse
    {
        if (! $deviceConnection->matchesIngestKey($request->header('X-Throughline-Key'))) {
            return response()->json([
                'message' => 'Invalid ingest key.',
            ], 401);
        }

        $validated = $request->validate([
            'metric_date' => ['required', 'date'],
            'external_event_id' => ['nullable', 'string', 'max:255'],
            'metrics' => ['required', 'array'],
            'metrics.readiness_score' => ['nullable', 'numeric', 'between:0,100'],
            'metrics.strain_score' => ['nullable', 'numeric', 'between:0,100'],
            'metrics.sleep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'metrics.sleep_need_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'metrics.sleep_performance_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'metrics.sleep_consistency_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'metrics.sleep_efficiency_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'metrics.rem_sleep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'metrics.slow_wave_sleep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'metrics.steps' => ['nullable', 'integer', 'min:0'],
            'metrics.distance_meters' => ['nullable', 'integer', 'min:0'],
            'metrics.calories_burned' => ['nullable', 'integer', 'min:0'],
            'metrics.active_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'metrics.resting_heart_rate' => ['nullable', 'integer', 'min:20', 'max:250'],
            'metrics.heart_rate_variability' => ['nullable', 'numeric', 'min:0'],
            'metrics.respiratory_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metrics.blood_oxygen_percent' => ['nullable', 'numeric', 'between:0,100'],
            'metrics.skin_temperature_celsius' => ['nullable', 'numeric', 'between:20,45'],
            'metrics.training_load' => ['nullable', 'numeric', 'min:0'],
            'raw_payload' => ['nullable', 'array'],
        ]);

        $result = $ingestionService->ingest($deviceConnection, $validated);

        return response()->json([
            'message' => 'Metrics accepted.',
            'device_connection' => [
                'public_id' => $deviceConnection->public_id,
                'provider' => $deviceConnection->provider->value,
            ],
            'snapshot' => [
                'metric_date' => $result['snapshot']->metric_date?->toDateString(),
                'readiness_score' => $result['snapshot']->readiness_score,
                'strain_score' => $result['snapshot']->strain_score,
                'sleep_performance_percentage' => $result['snapshot']->sleep_performance_percentage,
            ],
        ], 202);
    }
}
