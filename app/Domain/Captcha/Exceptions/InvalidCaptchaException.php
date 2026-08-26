<?php

declare(strict_types=1);

namespace App\Domain\Captcha\Exceptions;

use RuntimeException;

final class InvalidCaptchaException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Captcha verification failed.',
        );
    }
}
