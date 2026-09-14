<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\User\Exceptions\InvalidEmailException;
use App\Domain\User\Exceptions\InvalidPhoneException;
use App\Domain\User\Exceptions\InvalidUsernameException;
use App\Domain\User\Repositories\Contracts\UserProfileRepositoryInterface;

/**
 * Single source of truth for "is this X available" checks used across the
 * User domain. Centralising them here means every caller (Services now,
 * FormRequests/Actions later) gets the exact same rule — instead of each
 * one re-implementing its own existsByX() query and risking drift.
 */
final readonly class UserValidationService
{
    public function __construct(
        private AuthRepositoryInterface $users,
        private UserProfileRepositoryInterface $profiles,
    ) {}

    public function ensureEmailIsAvailable(string $email, ?int $exceptUserId = null): void
    {
        if ($this->users->emailExists($email, $exceptUserId)) {
            throw InvalidEmailException::alreadyTaken($email);
        }
    }

    public function ensurePhoneIsAvailable(string $phone, ?int $exceptUserId = null): void
    {
        if ($this->users->phoneExists($phone, $exceptUserId)) {
            throw InvalidPhoneException::alreadyTaken($phone);
        }
    }

    public function ensureUsernameIsAvailable(string $username): void
    {
        if ($this->profiles->existsByUsername($username)) {
            throw InvalidUsernameException::taken($username);
        }
    }
}
