<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\FulfilmentType;
use PHPUnit\Framework\TestCase;

/**
 * No database, no container, so PHPUnit's own TestCase — not Tests\TestCase.
 * Booting Laravel for a pure enum would add seconds to the suite for nothing.
 */
final class FulfilmentTypeTest extends TestCase
{
    public function test_values_are_derived_from_the_cases(): void
    {
        self::assertSame(['own_stock', 'dropship'], FulfilmentType::values());
        self::assertCount(count(FulfilmentType::cases()), FulfilmentType::values());
    }

    public function test_own_stock_needs_a_variant_stock_row_and_nothing_else(): void
    {
        self::assertTrue(FulfilmentType::OWN_STOCK->requiresLocalStock());
        self::assertFalse(FulfilmentType::OWN_STOCK->requiresDropshipSource());
        self::assertTrue(FulfilmentType::OWN_STOCK->stockCountIsAuthoritative());
    }

    public function test_dropship_needs_a_supplier_row_and_its_count_is_only_a_cache(): void
    {
        self::assertFalse(FulfilmentType::DROPSHIP->requiresLocalStock());
        self::assertTrue(FulfilmentType::DROPSHIP->requiresDropshipSource());
        self::assertFalse(FulfilmentType::DROPSHIP->stockCountIsAuthoritative());
    }

    /**
     * Every case must answer every method. A match() with a missing arm throws
     * UnhandledMatchError, so the day a third case is added this test fails
     * loudly instead of the new case silently taking some default.
     */
    public function test_every_case_answers_every_question(): void
    {
        foreach (FulfilmentType::cases() as $case) {
            self::assertIsBool($case->requiresLocalStock());
            self::assertIsBool($case->requiresDropshipSource());
            self::assertIsBool($case->stockCountIsAuthoritative());
            self::assertNotSame('', $case->label());
        }
    }
}
