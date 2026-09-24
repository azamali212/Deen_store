<?php

declare(strict_types=1);

namespace App\Domain\Seller\Support;

/**
 * Loose name comparison for the Layer 2 cross-checks — "Muhammad Azam Ali"
 * vs "M. Azam Ali", or "Zimal Fabrics (Pvt.) Ltd." vs "Zimal Fabrics".
 * A mismatch here only ever produces a WARNING for the admin, never a
 * block, because names are written in too many valid ways.
 */
final class NameMatcher
{
    // Honorifics and legal suffixes that carry no identity.
    private const NOISE = [
        'muhammad', 'mohammad', 'muhammed', 'mohammed', 'mohd', 'md', 'm',
        'syed', 'sayed', 'sayyed', 'hafiz', 'sheikh', 'shaikh',
        'mr', 'mrs', 'ms', 'miss', 'dr',
        'pvt', 'private', 'ltd', 'limited', 'llc', 'smc', 'co', 'company',
        'the', 'and', 'of',
    ];

    private const SIMILARITY_THRESHOLD = 85.0;

    public function matches(?string $a, ?string $b): bool
    {
        $left = $this->tokens($a);
        $right = $this->tokens($b);

        if ($left === [] || $right === []) {
            return false;
        }

        // One name contains every meaningful word of the other.
        if (array_diff($left, $right) === [] || array_diff($right, $left) === []) {
            return true;
        }

        // Small spelling differences ("Azam" vs "Azzam").
        similar_text(implode(' ', $left), implode(' ', $right), $percent);

        return $percent >= self::SIMILARITY_THRESHOLD;
    }

    /**
     * @return array<int, string>
     */
    private function tokens(?string $name): array
    {
        if ($name === null) {
            return [];
        }

        $clean = (string) preg_replace('/[^a-z0-9]+/', ' ', strtolower($name));

        $words = array_filter(
            explode(' ', $clean),
            static fn (string $word): bool => $word !== '' && ! in_array($word, self::NOISE, true),
        );

        $words = array_values(array_unique($words));
        sort($words);

        return $words;
    }
}
