<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

// This enum use for tell them the provider of the authentication process ,Login result ko standard banata hai.
enum LoginProvider: string
{
    case PASSWORD = 'password';
    case OTP = 'otp';
    case GOOGLE = 'google';
    case APPLE = 'apple';
    case FACEBOOK = 'facebook';
}