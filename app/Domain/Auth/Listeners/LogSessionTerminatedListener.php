<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\SessionTerminated;
use Illuminate\Support\Facades\Log;

final readonly class LogSessionTerminatedListener
{
    public function handle(
        SessionTerminated $event,
    ): void {

        Log::info(
            'User session terminated.',
            [
                'user_id' => $event->session->user_id,
                'token_id' => $event->session->token_id,
                'terminated_at' => now(),
            ]
        );
    }
}