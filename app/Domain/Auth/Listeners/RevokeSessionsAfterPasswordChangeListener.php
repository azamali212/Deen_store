<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Services\SessionService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final readonly class RevokeSessionsAfterPasswordChangeListener
{
    public function __construct(
        private SessionService $sessionService,
    ) {}

    public function handle(object $event): void
    {
        if (! property_exists($event, 'user')) {
            return;
        }

        if (! $event->user instanceof User) {
            return;
        }

        $this->sessionService->terminateAllForUser(
            $event->user
        );

        $event->user->tokens()->delete();

        Log::info('All sessions revoked after password change.', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);
    }
}