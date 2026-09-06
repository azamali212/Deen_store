<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use InvalidArgumentException;

trait HasDtoHelpers
{
    protected static function requiredString(
        array $data,
        string $key,
    ): string {
        $value = trim(
            (string) ($data[$key] ?? ''),
        );

        if ($value === '') {
            throw new InvalidArgumentException(
                "{$key} is required.",
            );
        }

        return $value;
    }

    protected static function cleanString(
        array $data,
        string $key,
    ): string {
        return trim(
            (string) ($data[$key] ?? ''),
        );
    }

    protected static function nullableString(
        array $data,
        string $key,
    ): ?string {
        $value = $data[$key] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value,
        );

        return $value === ''
            ? null
            : $value;
    }

    protected static function cleanEmail(
        array $data,
        string $key,
    ): string {
        return strtolower(
            trim(
                (string) ($data[$key] ?? ''),
            ),
        );
    }

    protected static function requiredInt(
        array $data,
        string $key,
    ): int {
        if (! isset($data[$key])) {
            throw new InvalidArgumentException(
                "{$key} is required.",
            );
        }

        return (int) $data[$key];
    }

    protected static function nullableInt(
        array $data,
        string $key,
    ): ?int {
        return isset($data[$key])
            ? (int) $data[$key]
            : null;
    }

    protected static function requiredFloat(
        array $data,
        string $key,
    ): float {
        if (! isset($data[$key])) {
            throw new InvalidArgumentException(
                "{$key} is required.",
            );
        }

        return (float) $data[$key];
    }

    protected static function nullableFloat(
        array $data,
        string $key,
    ): ?float {
        return isset($data[$key])
            ? (float) $data[$key]
            : null;
    }

    protected static function boolean(
        array $data,
        string $key,
        bool $default = false,
    ): bool {
        return (bool) ($data[$key] ?? $default);
    }

    protected static function nullableDate(
        array $data,
        string $key,
    ): ?string {
        return isset($data[$key])
            ? trim((string) $data[$key])
            : null;
    }
}
