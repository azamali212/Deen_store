<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use App\Domain\Audit\DTO\AuditContextDTO;
use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Services\DeviceFingerprintService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class AuditContextService
{
    public function __construct(
        private DeviceFingerprintService $deviceFingerprintService,
        private AuditCorrelationService $correlationService,
    ) {}

    public function resolve(Request $request, AuthPanel|string|null $panel = null): AuditContextDTO
    {
        $user = $request->user();

        $resolvedPanel = $panel instanceof AuthPanel
            ? $panel->value
            : $panel;

        return new AuditContextDTO(
            actorType: $user !== null ? $user::class : null,
            actorId: $user !== null ? (string) $user->getAuthIdentifier() : null,
            panel: $resolvedPanel,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            deviceName: $this->deviceFingerprintService->deviceName($request),
            requestId: $this->resolveRequestId($request),
            correlationId: $this->correlationService->resolve($request),
        );
    }

    public function system(): AuditContextDTO
    {
        return new AuditContextDTO(
            actorType: 'system',
            actorId: null,
            panel: null,
            ipAddress: null,
            userAgent: null,
            deviceName: null,
            requestId: (string) Str::uuid(),
            correlationId: $this->correlationService->current(),
        );
    }

    private function resolveRequestId(Request $request): string
    {
        $requestId = $request->attributes->get('request_id');

        if (is_string($requestId) && $requestId !== '') {
            return $requestId;
        }

        $requestId = (string) Str::uuid();

        $request->attributes->set(
            'request_id',
            $requestId,
        );

        return $requestId;
    }
}
