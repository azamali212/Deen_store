<?php

declare(strict_types=1);

namespace App\Domain\Audit\DTO;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class AuditContextDTO
{
    public function __construct(
        public ?string $actorType = null,
        public int|string|null $actorId = null,
        public ?string $panel = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $deviceName = null,
        public ?string $requestId = null,
        public ?string $correlationId = null,
    ) {}

    public static function fromRequest(
        Request $request,
        ?string $panel = null,
        ?string $deviceName = null,
        ?string $correlationId = null,
    ): self {
        $user = $request->user();

        return new self(
            actorType: $user !== null ? $user::class : null,
            actorId: $user?->getAuthIdentifier(),
            panel: $panel,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            deviceName: $deviceName,
            requestId: self::resolveRequestId($request),
            correlationId: $correlationId,
        );
    }

    public static function system(
        ?string $correlationId = null,
    ): self {
        return new self(
            actorType: 'system',
            correlationId: $correlationId,
        );
    }

    private static function resolveRequestId(Request $request): string
    {
        $requestId = $request->header('X-Request-ID');

        if (is_string($requestId) && Str::isUuid($requestId)) {
            return $requestId;
        }

        return (string) Str::uuid();
    }
}
