<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\EmailVerified;
use Illuminate\Support\Facades\Log;

final readonly class MarkEmailVerifiedListener
{
    public function handle(
        EmailVerified $event
    ): void {

        Log::info(
            'User email verified.',
            [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ]
        );
    }
}