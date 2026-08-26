<?php

declare(strict_types=1);

namespace App\Domain\Captcha\Services;

use App\Domain\Captcha\Contracts\CaptchaProviderInterface;
use App\Domain\Captcha\DTO\VerifyCaptchaDTO;
use App\Domain\Captcha\Exceptions\InvalidCaptchaException;

final readonly class CaptchaService
{
    public function __construct(
        private CaptchaProviderInterface $provider,
    ) {}

    public function verify(
        VerifyCaptchaDTO $dto,
    ): void {

        $verified = $this->provider
            ->verify(
                $dto,
            );

        if (! $verified) {
            throw new InvalidCaptchaException;
        }
    }
}
