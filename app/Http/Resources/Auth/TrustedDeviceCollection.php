<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

final class TrustedDeviceCollection extends ResourceCollection
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(
        Request $request,
    ): array {

        return [

            'success' => true,

            'message' => 'Trusted devices retrieved successfully.',

            'data' => TrustedDeviceResource::collection(
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