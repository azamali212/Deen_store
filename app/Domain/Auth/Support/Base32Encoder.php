<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

final class Base32Encoder
{
    public function normalize(string $secret): string
    {
        return strtoupper(
            str_replace(' ', '', $secret),
        );
    }
}