<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserCreated;
use App\Domain\Auth\Services\EmailVerificationService;

final readonly class RequestEmailVerificationListener
{
    public function __construct(
        private EmailVerificationService $service,
    ) {}

    public function handle(
        UserCreated $event
    ): void {

        $this->service->request(
            $event->user
        );
    }
}