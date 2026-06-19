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
        public LoginProvider $provider = LoginProvider::PASSWORD,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $deviceName = null,
        public bool $remember = false,
    ) {}

    //This function defines a public static method called fromArray that takes an array $data and an AuthPanel object $panel as parameters. It returns an instance of the LoginDTO class. The purpose of this method is to create a new LoginDTO object from the provided array data, extracting and cleaning the necessary values for email, password, provider, IP address, user agent, device name, and remember flag. It uses helper methods cleanEmail and nullableString to ensure proper formatting and handling of optional values.
    public static function fromArray(array $data, AuthPanel $panel): self
    {
        return new self(
            email: self::cleanEmail($data['email']),
            password: (string) $data['password'],
            panel: $panel,
            provider: isset($data['provider'])
                ? LoginProvider::from((string) $data['provider'])
                : LoginProvider::PASSWORD,
            ipAddress: self::nullableString($data, 'ip_address'),
            userAgent: self::nullableString($data, 'user_agent'),
            deviceName: self::nullableString($data, 'device_name'),
            remember: (bool) ($data['remember'] ?? false),
        );
    }

    //This function defines a private static method called cleanEmail that takes a mixed type parameter $email and returns a string. The purpose of this method is to clean and standardize the email address by converting it to lowercase and trimming any leading or trailing whitespace. It ensures that the email address is in a consistent format for further processing or storage.
    private static function cleanEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    //This function defines a private static method called nullableString that takes an array $data and a string $key as parameters. It returns a nullable string (string or null). The purpose of this method is to retrieve the value associated with the specified key from the provided array. If the key exists in the array and its value is not an empty string, it trims any leading or trailing whitespace and returns the cleaned string. If the key does not exist or its value is an empty string, it returns null. This method is useful for safely extracting optional string values from an array while ensuring they are properly formatted.
    private static function nullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && $data[$key] !== ''
            ? trim((string) $data[$key])
            : null;
    }
}