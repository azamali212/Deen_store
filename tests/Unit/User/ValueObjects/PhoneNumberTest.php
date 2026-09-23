<?php

declare(strict_types=1);

namespace Tests\Unit\User\ValueObjects;

use App\Domain\User\ValueObjects\PhoneNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * PhoneNumber validates against: ^\+?[1-9]\d{7,14}$
 * - optional leading "+"
 * - first digit must be 1-9 (no leading zero)
 * - followed by 7 to 14 more digits (total 8-15 digits after the "+")
 */
final class PhoneNumberTest extends TestCase
{
    public function test_it_accepts_a_valid_number_with_plus_and_country_code(): void
    {
        $phone = new PhoneNumber('+923001234567');

        $this->assertSame('+923001234567', $phone->value());
    }

    public function test_it_accepts_a_valid_number_without_a_plus(): void
    {
        $phone = new PhoneNumber('923001234567');

        $this->assertSame('923001234567', $phone->value());
    }

    public function test_it_accepts_the_shortest_allowed_length_of_8_digits(): void
    {
        // 1 leading digit + 7 more = 8 digits total, the regex's minimum.
        $phone = new PhoneNumber('12345678');

        $this->assertSame('12345678', $phone->value());
    }

    public function test_it_accepts_the_longest_allowed_length_of_15_digits(): void
    {
        // 1 leading digit + 14 more = 15 digits total, the regex's maximum.
        $phone = new PhoneNumber('123456789012345');

        $this->assertSame('123456789012345', $phone->value());
    }

    public function test_it_rejects_a_number_that_is_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Only 7 digits — one short of the minimum.
        new PhoneNumber('1234567');
    }

    public function test_it_rejects_a_number_that_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // 16 digits — one over the maximum.
        new PhoneNumber('1234567890123456');
    }

    public function test_it_rejects_a_number_starting_with_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PhoneNumber('0923001234567');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidFormatProvider(): array
    {
        return [
            'contains letters' => ['923abc1234'],
            'contains a space' => ['923 0012345'],
            'contains a dash' => ['923-0012345'],
            'double plus sign' => ['++923001234567'],
            'empty string' => [''],
        ];
    }

    #[DataProvider('invalidFormatProvider')]
    public function test_it_rejects_malformed_numbers(string $invalidNumber): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PhoneNumber($invalidNumber);
    }

    public function test_from_trims_surrounding_whitespace(): void
    {
        $phone = PhoneNumber::from('  +923001234567  ');

        $this->assertSame('+923001234567', $phone->value());
    }

    public function test_formatted_currently_just_returns_the_raw_value(): void
    {
        // Documents current behavior: formatted() does no actual
        // formatting yet (no spacing/grouping) — it's an alias of value().
        $phone = new PhoneNumber('+923001234567');

        $this->assertSame($phone->value(), $phone->formatted());
    }

    public function test_two_identical_numbers_are_equal(): void
    {
        $first = new PhoneNumber('+923001234567');
        $second = new PhoneNumber('+923001234567');

        $this->assertTrue($first->equals($second));
    }

    public function test_two_different_numbers_are_not_equal(): void
    {
        $first = new PhoneNumber('+923001234567');
        $second = new PhoneNumber('+923009999999');

        $this->assertFalse($first->equals($second));
    }

    public function test_it_casts_to_string_as_the_raw_value(): void
    {
        $phone = new PhoneNumber('+923001234567');

        $this->assertSame('+923001234567', (string) $phone);
    }
}
