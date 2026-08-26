<?php

declare(strict_types=1);

namespace App\Domain\Captcha\Actions;

use App\Domain\Captcha\DTO\VerifyCaptchaDTO;
use App\Domain\Captcha\Services\CaptchaService;

final readonly class VerifyCaptchaAction
{
    public function __construct(
        private CaptchaService $service,
    ) {}

    public function execute(
        VerifyCaptchaDTO $dto,
    ): void {

        $this->service
            ->verify(
                $dto,
            );
    }
}
