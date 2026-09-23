<?php

declare(strict_types=1);

namespace Tests\Unit\User\ValueObjects;

use App\Domain\User\ValueObjects\PostalCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PostalCodeTest extends TestCase
{
    public function test_it_accepts_a_valid_postal_code(): void
    {
        $postalCode = new PostalCode('74200');

        $this->assertSame('74200', $postalCode->value());
    }

    public function test_it_rejects_an_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PostalCode('');
    }

    public function test_from_trims_and_uppercases_the_value(): void
    {
        $postalCode = PostalCode::from('  sw1a 1aa  ');

        $this->assertSame('SW1A 1AA', $postalCode->value());
    }

    public function test_from_rejects_a_whitespace_only_value_because_it_trims_to_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // from() trims first, so "   " becomes "" before it ever reaches
        // the constructor — this is the path a controller/DTO actually uses.
        PostalCode::from('   ');
    }

    public function test_the_constructor_does_not_catch_a_whitespace_only_value_when_called_directly(): void
    {
        // This documents a real gap: the constructor only rejects the
        // exact empty string (`=== ''`), not blank/whitespace-only input.
        // from() protects against this by trimming first, but anything
        // that calls `new PostalCode(...)` directly (bypassing from())
        // can slip a "postal code" of just spaces straight through.
        $postalCode = new PostalCode('   ');

        $this->assertSame('   ', $postalCode->value());
    }

    public function test_two_identical_postal_codes_are_equal(): void
    {
        $first = new PostalCode('74200');
        $second = new PostalCode('74200');

        $this->assertTrue($first->equals($second));
    }

    public function test_two_different_postal_codes_are_not_equal(): void
    {
        $first = new PostalCode('74200');
        $second = new PostalCode('75500');

        $this->assertFalse($first->equals($second));
    }

    public function test_it_casts_to_string_as_the_raw_value(): void
    {
        $postalCode = new PostalCode('74200');

        $this->assertSame('74200', (string) $postalCode);
    }
}
