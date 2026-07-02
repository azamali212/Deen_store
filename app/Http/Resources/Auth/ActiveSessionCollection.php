<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

final class ActiveSessionCollection extends ResourceCollection
{
    public function toArray(
        Request $request,
    ): array {

        return [

            'success' => true,

            'message' => 'Active sessions retrieved successfully.',

            'data' => ActiveSessionResource::collection(
                $this->collection
            ),

            'meta' => [

                'request_id' => $request->header(
                    'X-Request-Id'
                ) ?? (string) Str::ulid(),

                'timestamp' => now()->toISOString(),

            ],

        ];
    }
}