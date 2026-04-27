<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

final class LoginOtpSent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ?int $userId,
        public readonly string $identifier,
        public readonly string $otpCode,
        public readonly Carbon $expiresAt,
        public readonly string $purpose,
    ) {}
}