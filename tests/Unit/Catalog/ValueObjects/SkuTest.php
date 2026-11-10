<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\ValueObjects;

use App\Domain\Catalog\ValueObjects\Sku;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/*
|--------------------------------------------------------------------------
| YE FILE KYA HAI
|--------------------------------------------------------------------------
| Sku ke chaaron check aur chaaron method ka saboot. Koi database nahi,
| koi Laravel nahi, isi liye PHPUnit ka apna TestCase — ek pure value
| object ke liye poora framework uthana suite mein fazool seconds dalta
| hai.
|
|--------------------------------------------------------------------------
| KU BANAI
|--------------------------------------------------------------------------
| Kyunki jo check aazmaya na gaya, wo check nahi hota. Sku ka pehla
| lambai-check ek dafa `strlen` ke baghair likha gaya tha aur CHUP CHAAP
| guzarta raha — na error, na nateeja. Aisi ghalati sirf test pakadta hai.
|
|--------------------------------------------------------------------------
| KAHAN ZAROORAT PAREGI
|--------------------------------------------------------------------------
| `php artisan test` — poora suite (L1). C6 ki migration likhte waqt bhi,
| kyunki 64 ki hadd dono jagah ek honi chahiye.
|
|--------------------------------------------------------------------------
| AGAR YE FILE NA HOTI
|--------------------------------------------------------------------------
| Kal koi pattern mein `a-z` barha deta "seller ki aasani" ke liye, aur
| chhote harf wale SKU database mein chale jate — aur masla mahino baad
| duplicate rows ki shakal mein nikalta.
|
|--------------------------------------------------------------------------
| KIS FILE SE JURI HAI
|--------------------------------------------------------------------------
| app/Domain/Catalog/ValueObjects/Sku.php
*/
final class SkuTest extends TestCase
{
    public function test_a_valid_code_is_accepted(): void
    {
        self::assertSame('ZML-LAWN-0431', (new Sku('ZML-LAWN-0431'))->code());
        self::assertSame('ZML_0431', (new Sku('ZML_0431'))->code());
        self::assertSame('ABC', (new Sku('ABC'))->code());
    }

    /** 64 is the boundary, and a boundary is only proven from both sides. */
    public function test_the_length_boundary_is_exact(): void
    {
        self::assertSame(64, strlen((new Sku(str_repeat('A', 64)))->code()));

        $this->expectException(InvalidArgumentException::class);
        new Sku(str_repeat('A', 65));
    }

    public static function badCodes(): array
    {
        return [
            'empty' => [''],
            'two characters' => ['AB'],
            'sixty five characters' => [str_repeat('A', 65)],
            'a space inside' => ['ZML 0431'],
            'lower case' => ['zml-0431'],
            'an at sign' => ['ZML@0431'],
            'a dot' => ['ZML.0431'],
            'urdu letters' => ['کتاب-0431'],
            'already trimmed input is required' => ['  ABC  '],
        ];
    }

    #[DataProvider('badCodes')]
    public function test_a_bad_code_is_refused(string $code): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Sku($code);
    }

    /**
     * The message is not decoration. A seller's CSV of 4,000 rows with 188
     * failures (D-CAT-8) is only fixable if each row says what was wrong AND
     * what was required.
     */
    public function test_the_message_says_what_was_required_and_what_arrived(): void
    {
        try {
            new Sku('AB');
            self::fail('A two-character SKU should have been refused.');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('at least 3', $e->getMessage());
            self::assertStringContainsString('got 2', $e->getMessage());
        }
    }

    public function test_from_user_input_trims_and_upper_cases(): void
    {
        self::assertSame('ZML-0431', Sku::fromUserInput('  zml-0431  ')->code());
        self::assertSame('ZML_0431', Sku::fromUserInput("\tzml_0431\n")->code());
    }

    /**
     * The split is the point: cleaning belongs to fromUserInput, checking to
     * the constructor. If the constructor cleaned too, dirty input would pass
     * silently and nobody would ever learn where it came from.
     */
    public function test_the_constructor_does_not_clean_its_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Sku('  ZML-0431  ');
    }

    /**
     * Two separate objects with the same code must be equal. `===` would say
     * false here, because on objects it asks "the same object?" and not "the
     * same contents?" — unlike an enum case, which is a single object for the
     * whole program.
     */
    public function test_two_separate_objects_with_the_same_code_are_equal(): void
    {
        $a = new Sku('ZML-0431');
        $b = new Sku('ZML-0431');

        self::assertTrue($a->equals($b));
        self::assertFalse($a === $b);
    }

    public function test_different_codes_are_not_equal(): void
    {
        self::assertFalse((new Sku('ZML-0431'))->equals(new Sku('ZML-0432')));
    }

    public function test_it_can_be_used_as_a_string(): void
    {
        $sku = new Sku('ZML-0431');

        self::assertSame('ZML-0431', (string) $sku);
        self::assertSame('SKU ZML-0431 is invalid', "SKU {$sku} is invalid");
    }
}
