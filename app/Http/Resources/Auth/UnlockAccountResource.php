<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

final class UnlockAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Account unlocked successfully.',
            'data' => [
                'user' => [
                    'id' => $this->id,
                    'uuid' => $this->uuid,
                    'name' => $this->name,
                    'email' => $this->email,
                    'status' => $this->status,
                    'locked_at' => $this->locked_at,
                    'locked_until' => $this->locked_until,
                    'failed_login_attempts' => $this->failed_login_attempts,
                ],
            ],
            'meta' => [
                'request_id' => $request->header('X-Request-Id') ?? (string) Str::ulid(),
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}