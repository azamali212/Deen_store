<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use Illuminate\Support\Str;

final class RecoveryCodeGenerator
{
    public function generate(
        int $count = 8,
    ): array {

        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(
                Str::random(10),
            );
        }

        return $codes;
    }
}