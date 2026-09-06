<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class ProfileIncompleteException extends DomainException
{
    public static function missingFields(array $fields): self
    {
        $list = implode(', ', $fields);

        return (new self("Profile is incomplete. Missing fields: {$list}"))
            ->withContext(['missing_fields' => $fields]);
    }
}
