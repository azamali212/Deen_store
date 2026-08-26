<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Enums\AuthPanel;

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
}
