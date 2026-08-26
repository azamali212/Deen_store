<?php

declare(strict_types=1);

namespace App\Domain\Captcha\DTO;

final readonly class VerifyCaptchaDTO
{
    public function __construct(
        public string $token,
        public string $ipAddress,
    ) {}
}
