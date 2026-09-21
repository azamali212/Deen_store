<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Permissions\Enums\SystemRole;

final class SellerAuthController extends BaseAuthController
{
    protected function panel(): AuthPanel
    {
        return AuthPanel::SELLER;
    }

    protected function canRegister(): bool
    {
        return false;
    }

    // Deferred: sellers are upgraded from an existing customer account
    // via an approval request, not by registering fresh (see Seller
    // domain notes). No direct self-registration for this panel yet.
    protected function selfRegistrationRole(): ?SystemRole
    {
        return null;
    }
}
