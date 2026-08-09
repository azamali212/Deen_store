<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditCorrelationService
{
    private ?string $correlationId = null;

    public function resolve(Request $request): string
    {
        if ($this->correlationId !== null) {
            return $this->correlationId;
        }

        $incoming = $request->header('X-Correlation-ID');

        $this->correlationId = is_string($incoming) && $incoming !== ''
            ? $incoming
            : (string) Str::uuid();

        return $this->correlationId;
    }

    public function current(): ?string
    {
        return $this->correlationId;
    }

    public function set(string $correlationId): void
    {
        $this->correlationId = $correlationId;
    }
}
