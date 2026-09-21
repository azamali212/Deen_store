<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\Auth\Events\UserCreated;
use App\Domain\User\DTO\UpdatePreferenceDTO;
use App\Domain\User\Services\PreferenceService;

/**
 * Cross-domain listener: reacts to Auth domain's UserCreated event (not a
 * User-domain event) so every new signup automatically gets a default
 * preferences row — no Controller/Action anywhere has to remember to call
 * PreferenceService itself.
 */
final readonly class ProvisionDefaultPreferencesListener
{
    public function __construct(
        private PreferenceService $preferenceService,
    ) {}

    public function handle(UserCreated $event): void
    {
        $this->preferenceService->savePreferences(
            $event->user->id,
            UpdatePreferenceDTO::fromArray([]),
        );
    }
}
