<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

//This enum use for tell them the status of the authentication process ,Login result ko standard banata hai.
enum AuthStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case OTP_REQUIRED = 'otp_required';
    case BLOCKED = 'blocked';
    case LOCKED = 'locked';
    case SUSPICIOUS = 'suspicious';
}