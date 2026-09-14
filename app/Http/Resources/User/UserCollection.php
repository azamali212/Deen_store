<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

final class UserCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => UserResource::collection($this->collection),
            'meta' => [
                'request_id' => $request->header('X-Request-Id') ?? (string) Str::ulid(),
                'timestamp' => now()->toISOString(),
                'pagination' => [
                    'current_page' => $this->resource->currentPage(),
                    'per_page' => $this->resource->perPage(),
                    'total' => $this->resource->total(),
                    'last_page' => $this->resource->lastPage(),
                ],
            ],
        ];
    }
}
