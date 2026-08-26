<?php

declare(strict_types=1);

namespace App\Domain\Captcha\Contracts;

use App\Domain\Captcha\DTO\VerifyCaptchaDTO;

interface CaptchaProviderInterface
{
    public function verify(
        VerifyCaptchaDTO $dto,
    ): bool;
}
