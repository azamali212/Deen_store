<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\AuthPanel;

final readonly class LogoutDTO
{
    public function __construct(
        public string $userId,
        public AuthPanel $panel,
        public ?string $tokenId = null,
        public bool $logoutAllDevices = false,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    // ipAddress/userAgent come from the controller's $request->ip() /
    // $request->userAgent() (never from client-submitted body fields —
    // LogoutRequest doesn't validate any such fields, on purpose).
    public static function fromArray(
        array $data,
        string $userId,
        AuthPanel $panel,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        return new self(
            userId: $userId,
            panel: $panel,
            tokenId: self::nullableString($data, 'token_id'),
            logoutAllDevices: (bool) ($data['logout_all_devices'] ?? false),
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    private static function nullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && $data[$key] !== ''
            ? trim((string) $data[$key])
            : null;
    }
}