<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\PhoneVerificationService;
use App\Domain\User\ValueObjects\PhoneNumber;
use App\Models\User;

final readonly class RequestPhoneVerificationAction
{
    public function __construct(
        private PhoneVerificationService $service,
    ) {}

    public function execute(User $user, PhoneNumber $phone): void
    {
        $this->service->sendCode($user, $phone);
    }
}
