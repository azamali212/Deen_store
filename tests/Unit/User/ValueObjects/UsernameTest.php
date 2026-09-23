<?php

declare(strict_types=1);

namespace Tests\Unit\User\ValueObjects;

use App\Domain\User\Exceptions\InvalidUsernameException;
use App\Domain\User\ValueObjects\Username;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pure Unit test — no HTTP, no database, no Laravel app boot at all.
 * Username is a plain value object, so we just call `new Username(...)`
 * directly and check what comes back or what exception gets thrown.
 */
final class UsernameTest extends TestCase
{
    public function test_it_accepts_a_valid_username(): void
    {
        $username = new Username('azam_ali');

        $this->assertSame('azam_ali', $username->value());
    }

    public function test_it_lowercases_and_trims_the_value(): void
    {
        $username = new Username('  Azam_ALI  ');

        $this->assertSame('azam_ali', $username->value());
    }

    public function test_it_accepts_the_shortest_allowed_length_of_3_characters(): void
    {
        $username = new Username('abc');

        $this->assertSame('abc', $username->value());
    }

    public function test_it_accepts_the_longest_allowed_length_of_20_characters(): void
    {
        $longest = str_repeat('a', 20);

        $username = new Username($longest);

        $this->assertSame($longest, $username->value());
    }

    public function test_it_rejects_a_username_shorter_than_3_characters(): void
    {
        $this->expectException(InvalidUsernameException::class);

        new Username('ab');
    }

    public function test_it_rejects_a_username_longer_than_20_characters(): void
    {
        $this->expectException(InvalidUsernameException::class);

        new Username(str_repeat('a', 21));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidCharacterProvider(): array
    {
        return [
            'contains a space' => ['azam ali'],
            'contains an at-sign' => ['azam@ali'],
            'contains an exclamation mark' => ['azam!'],
            'contains a hash' => ['#azam'],
            'contains a slash' => ['azam/ali'],
        ];
    }

    #[DataProvider('invalidCharacterProvider')]
    public function test_it_rejects_usernames_with_disallowed_characters(string $invalidUsername): void
    {
        $this->expectException(InvalidUsernameException::class);

        new Username($invalidUsername);
    }

    public function test_from_lowercases_and_trims_before_constructing(): void
    {
        $username = Username::from('  AZAM_Ali  ');

        $this->assertSame('azam_ali', $username->value());
    }

    public function test_two_usernames_with_different_casing_are_equal(): void
    {
        $first = new Username('Azam_Ali');
        $second = new Username('azam_ali');

        $this->assertTrue($first->equals($second));
    }

    public function test_two_different_usernames_are_not_equal(): void
    {
        $first = new Username('azam_ali');
        $second = new Username('zimal_store');

        $this->assertFalse($first->equals($second));
    }

    public function test_it_casts_to_string_as_the_normalized_value(): void
    {
        $username = new Username('Azam_Ali');

        $this->assertSame('azam_ali', (string) $username);
    }
}
