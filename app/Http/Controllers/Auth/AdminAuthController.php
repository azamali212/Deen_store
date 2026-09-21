<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Permissions\Enums\SystemRole;

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

    protected function selfRegistrationRole(): ?SystemRole
    {
        return null;
    }
}
