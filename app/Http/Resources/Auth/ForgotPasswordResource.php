<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

final class ForgotPasswordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'success' => true,

            'message' => 'Password reset link sent successfully.',

            'data' => null,

            'meta' => [

                'request_id' => $request->header(
                    'X-Request-Id'
                ) ?? (string) Str::ulid(),

                'timestamp' => now()->toISOString(),

            ],

        ];
    }
}