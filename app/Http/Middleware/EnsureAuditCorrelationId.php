<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Audit\Services\AuditCorrelationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureAuditCorrelationId
{
    public function __construct(
        private AuditCorrelationService $correlationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID');

        if (! is_string($requestId) || $requestId === '') {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set(
            'request_id',
            $requestId,
        );

        $correlationId = $this->correlationService->resolve(
            $request,
        );

        $request->attributes->set(
            'correlation_id',
            $correlationId,
        );

        $response = $next($request);

        $response->headers->set(
            'X-Request-ID',
            $requestId,
        );

        $response->headers->set(
            'X-Correlation-ID',
            $correlationId,
        );

        return $response;
    }
}
