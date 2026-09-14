<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class UserNotFoundException extends DomainException
{
    public static function withId(int|string $id): self
    {
        return (new self("User not found with ID: {$id}"))
            ->withContext(['user_id' => $id]);
    }

    public static function withEmail(string $email): self
    {
        return (new self("User not found with email: {$email}"))
            ->withContext(['email' => $email]);
    }
}
