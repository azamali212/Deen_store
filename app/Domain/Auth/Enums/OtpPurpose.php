<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;
// This enum use for tell them the purpose of the authentication process ,OTP har jagah same purpose ke liye nahi hota.
enum OtpPurpose: string
{
    case LOGIN = 'login';
    case ADMIN_LOGIN = 'admin_login';
    case PASSWORD_RESET = 'password_reset';
    case EMAIL_VERIFICATION = 'email_verification';
    case STEP_UP_AUTH = 'step_up_auth';
}