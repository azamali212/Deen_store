<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\DTO\VerifyPhoneDTO;
use App\Domain\User\Events\PhoneVerified;
use App\Domain\User\Exceptions\InvalidVerificationCodeException;
use App\Domain\User\Services\PhoneVerificationService;
use App\Models\User;

final readonly class VerifyPhoneAction
{
    public function __construct(
        private PhoneVerificationService $service,
    ) {}

    public function execute(User $user, VerifyPhoneDTO $dto): User
    {
        $confirmed = $this->service->confirm(
            $user,
            $dto->phone,
            $dto->code,
        );

        if (! $confirmed) {
            throw InvalidVerificationCodeException::forPhone();
        }

        event(new PhoneVerified($user));

        return $user->fresh();
    }
}
