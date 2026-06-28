<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Services\DeviceFingerprintService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureTrustedDevice
{
    public function __construct(
        private DeviceFingerprintService $fingerprintService,
        private AuthRepositoryInterface $repository,
    ) {}

    public function handle(Request $request, Closure $next, string $panel): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        $fingerprint = $this->fingerprintService->generate(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            deviceName: $this->fingerprintService->deviceName($request),
            panel: $panel,
        );

        $device = $this->repository->findTrustedDevice(
            $user->id,
            $fingerprint
        );

       

        if ($device === null || ! $device->isTrusted()) {
            abort(403, 'This device is not trusted.');
        }

        return $next($request);
    }
}