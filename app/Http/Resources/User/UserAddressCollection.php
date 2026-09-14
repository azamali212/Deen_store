<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

final class UserAddressCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Addresses retrieved successfully.',
            'data' => UserAddressResource::collection($this->collection),
            'meta' => [
                'request_id' => $request->header('X-Request-Id') ?? (string) Str::ulid(),
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}
