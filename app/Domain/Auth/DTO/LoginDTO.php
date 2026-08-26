<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\LoginProvider;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public AuthPanel $panel,
        public LoginProvider $provider,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $deviceName,
        public bool $remember,
        // Captcha
        public string $captchaToken,
    ) {}

    public static function fromArray(
        array $data,
        AuthPanel $panel,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceName = null,

    ): self {
        return new self(
            email: self::cleanEmail($data['email']),
            password: (string) $data['password'],
            captchaToken: $data['captcha_token'],
            panel: $panel,
            provider: isset($data['provider'])
                ? LoginProvider::from((string) $data['provider'])
                : LoginProvider::PASSWORD,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceName: $deviceName,
            remember: (bool) ($data['remember'] ?? false),
        );
    }

    private static function cleanEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }
}
