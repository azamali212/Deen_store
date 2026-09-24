<?php

declare(strict_types=1);

namespace Tests\Unit\Seller;

use App\Domain\Seller\Support\NameMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NameMatcherTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function matchingNames(): array
    {
        return [
            'honorific added' => ['Muhammad Azam Ali', 'Azam Ali'],
            'honorific abbreviated' => ['M. Azam Ali', 'Muhammad Azam Ali'],
            'different case and spacing' => ['AZAM   ALI', 'azam ali'],
            'legal suffix on business' => ['Zimal Fabrics (Pvt.) Ltd.', 'Zimal Fabrics'],
            'small spelling difference' => ['Azam Ali', 'Azzam Ali'],
            'word order' => ['Ali Azam', 'Azam Ali'],
        ];
    }

    #[DataProvider('matchingNames')]
    public function test_names_that_should_match(?string $a, ?string $b): void
    {
        $this->assertTrue((new NameMatcher)->matches($a, $b));
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function differentNames(): array
    {
        return [
            'different person' => ['Azam Ali', 'Bilal Khan'],
            'different business' => ['Zimal Fabrics', 'Karachi Electronics'],
            'one side missing' => ['Azam Ali', null],
            'empty string' => ['', 'Azam Ali'],
            'only honorifics' => ['Muhammad', 'Muhammad'],
        ];
    }

    #[DataProvider('differentNames')]
    public function test_names_that_should_not_match(?string $a, ?string $b): void
    {
        $this->assertFalse((new NameMatcher)->matches($a, $b));
    }
}
