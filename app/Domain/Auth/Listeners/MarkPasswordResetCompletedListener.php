<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\PasswordResetCompleted;
use Illuminate\Support\Facades\Log;

final readonly class MarkPasswordResetCompletedListener
{
    public function handle(
        PasswordResetCompleted $event
    ): void {

        Log::info(
            'User password reset completed.',
            [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ]
        );
    }
}