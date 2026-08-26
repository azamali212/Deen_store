<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Enums\AuthPanel;

final class AdminAuthController extends BaseAuthController
{
    protected function panel(): AuthPanel
    {
        return AuthPanel::ADMIN;
    }

    protected function canRegister(): bool
    {
        return true;
    }
}
