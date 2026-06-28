<?php

declare(strict_types=1);

namespace App\Domain\Auth\Jobs;

use App\Domain\Auth\Notifications\LoginOtpNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendOtpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly User $user,
        private readonly string $identifier,
        private readonly string $purpose,
    ) {}

    public function handle(): void
    {
        $this->user->notify(
            new LoginOtpNotification(
                purpose: $this->purpose,
                identifier: $this->identifier,
            )
        );
    }
}