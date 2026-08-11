<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProgressEntryRequest;
use App\Http\Resources\Api\V1\ProgressEntryResource;
use App\Models\ProgressEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $entries = ProgressEntry::query()
            ->where('athlete_id', $request->user()->id)
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('logged_on', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('logged_on', '<=', $to))
            ->latest('logged_on')
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->paginated($entries, ProgressEntryResource::class);
    }

    public function store(ProgressEntryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $entry = ProgressEntry::query()->updateOrCreate([
            'organization_id' => $request->user()->current_organization_id,
            'athlete_id' => $request->user()->id,
            'logged_on' => $data['logged_on'],
        ], [
            'weight' => $data['weight_kg'] ?? null,
            'calories' => $data['calories_kcal'] ?? null,
            'protein' => $data['protein_g'] ?? null,
            'hydration' => $data['hydration_ml'] ?? null,
            'sleep_quality' => $data['sleep_quality'] ?? null,
            'soreness' => $data['soreness'] ?? null,
            'energy' => $data['energy'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->success(new ProgressEntryResource($entry), status: $entry->wasRecentlyCreated ? 201 : 200);
    }
}
