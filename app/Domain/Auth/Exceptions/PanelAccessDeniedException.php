<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

final class PanelAccessDeniedException extends AuthException
{
    public function __construct(
        string $panel
    ) {
        parent::__construct(
            "Access denied for {$panel} panel.",
            403
        );
    }
}