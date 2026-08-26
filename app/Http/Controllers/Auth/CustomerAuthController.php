<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Enums\AuthPanel;

final class CustomerAuthController extends BaseAuthController
{
    protected function panel(): AuthPanel
    {
        return AuthPanel::CUSTOMER;
    }

    protected function canRegister(): bool
    {
        return false;
    }
}
