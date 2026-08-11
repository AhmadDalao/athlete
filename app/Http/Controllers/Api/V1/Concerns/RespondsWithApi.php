<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait RespondsWithApi
{
    protected function success(mixed $data, array $meta = [], array $links = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
            'links' => (object) $links,
        ], $status);
    }

    /** @param class-string<JsonResource> $resource */
    protected function paginated(LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        return $this->success(
            $resource::collection($paginator->getCollection()),
            [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'previous' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        );
    }

    protected function pageSize(int|string|null $value, int $default = 25): int
    {
        $size = filter_var($value, FILTER_VALIDATE_INT) ?: $default;

        return max(1, min($size, 100));
    }
}
