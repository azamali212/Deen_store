<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\User\DTO\UpdatePreferenceDTO;
use App\Domain\User\Exceptions\PreferenceNotFoundException;
use App\Domain\User\Repositories\Contracts\UserPreferenceRepositoryInterface;
use App\Models\UserPreference;
use Illuminate\Database\UniqueConstraintViolationException;

final class UserPreferenceRepository implements UserPreferenceRepositoryInterface
{
    /**
     * Fetch a user's preference row, if it exists.
     * Returns null when the user has never saved preferences yet.
     */
    public function findByUserId(int $userId): ?UserPreference
    {
        return UserPreference::query()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Create a brand-new preference row.
     * Callers must be sure one does not already exist — `user_id` carries
     * a unique index at the database level, so a duplicate insert fails
     * loudly (and fast) instead of silently overwriting data.
     */
    public function create(int $userId, UpdatePreferenceDTO $dto): UserPreference
    {
        return UserPreference::query()->create(
            $this->attributes($userId, $dto),
        );
    }

    /**
     * Update an existing preference row.
     *
     * @throws PreferenceNotFoundException when the user has no preferences yet.
     */
    public function update(int $userId, UpdatePreferenceDTO $dto): UserPreference
    {
        $preference = $this->findByUserId($userId)
            ?? throw PreferenceNotFoundException::withUserId($userId);

        $preference->update(
            $this->attributes($userId, $dto, withUserId: false),
        );

        return $preference->refresh();
    }

    /**
     * Save preferences whether or not a row already exists yet. This is the
     * method real callers (e.g. a "Save settings" screen) should use, since
     * it never fails just because a first-time user has no row.
     *
     * Concurrency note: under heavy real-time traffic, two requests for the
     * same user (two devices, a retried request) can both race past the
     * initial lookup and both attempt an INSERT. The database's unique
     * index on `user_id` rejects the losing insert — we catch that specific
     * failure and convert it into a normal update instead of bubbling up a
     * 500 error to the client.
     */
    public function updateOrCreate(int $userId, UpdatePreferenceDTO $dto): UserPreference
    {
        $attributes = $this->attributes($userId, $dto, withUserId: false);

        try {
            return UserPreference::query()->updateOrCreate(
                ['user_id' => $userId],
                $attributes,
            );
        } catch (UniqueConstraintViolationException) {
            return $this->update($userId, $dto);
        }
    }

    /**
     * Remove a user's preferences entirely (account deletion, reset-to-default).
     */
    public function delete(int $userId): bool
    {
        return UserPreference::query()
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Map the DTO onto database column names in exactly one place, so every
     * write path (create / update / updateOrCreate) stays in sync when a
     * new preference field is added later.
     */
    private function attributes(int $userId, UpdatePreferenceDTO $dto, bool $withUserId = true): array
    {
        $attributes = [
            'language' => $dto->language,
            'currency' => $dto->currency,
            'timezone' => $dto->timezone,
            'theme' => $dto->theme,
            'email_notifications' => $dto->emailNotifications,
            'sms_notifications' => $dto->smsNotifications,
            'push_notifications' => $dto->pushNotifications,
            'marketing_notifications' => $dto->marketingNotifications,
        ];

        return $withUserId ? ['user_id' => $userId] + $attributes : $attributes;
    }
}
