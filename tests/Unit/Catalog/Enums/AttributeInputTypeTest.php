<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\AttributeInputType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AttributeInputTypeTest extends TestCase
{
    public function test_values_are_derived_from_the_cases(): void
    {
        self::assertSame(
            ['select', 'multiselect', 'text', 'number', 'boolean'],
            AttributeInputType::values(),
        );
    }

    /** D-CAT-1: only the two list types read from attribute_values. */
    public function test_only_the_list_types_use_the_platform_value_list(): void
    {
        self::assertTrue(AttributeInputType::SELECT->usesPlatformValueList());
        self::assertTrue(AttributeInputType::MULTISELECT->usesPlatformValueList());
        self::assertFalse(AttributeInputType::TEXT->usesPlatformValueList());
        self::assertFalse(AttributeInputType::NUMBER->usesPlatformValueList());
        self::assertFalse(AttributeInputType::BOOLEAN->usesPlatformValueList());
    }

    public function test_only_multiselect_allows_more_than_one_value(): void
    {
        self::assertTrue(AttributeInputType::MULTISELECT->allowsMultipleValues());

        foreach (AttributeInputType::cases() as $case) {
            if ($case !== AttributeInputType::MULTISELECT) {
                self::assertFalse($case->allowsMultipleValues(), $case->value);
            }
        }
    }

    /**
     * Only SELECT can be a variant axis, and the reason is in the schema:
     * variant_attribute_values carries unique(product_variant_id,
     * attribute_id), so a variant physically cannot hold two values.
     */
    public function test_only_select_can_define_variants(): void
    {
        self::assertTrue(AttributeInputType::SELECT->canDefineVariants());

        foreach (AttributeInputType::cases() as $case) {
            if ($case !== AttributeInputType::SELECT) {
                self::assertFalse($case->canDefineVariants(), $case->value);
            }
        }
    }

    public static function filters(): array
    {
        return [
            'select is chips' => [AttributeInputType::SELECT, 'chips'],
            'multiselect is chips' => [AttributeInputType::MULTISELECT, 'chips'],
            'boolean is a toggle' => [AttributeInputType::BOOLEAN, 'toggle'],
            'number is a range' => [AttributeInputType::NUMBER, 'range'],
            'text cannot be filtered at all' => [AttributeInputType::TEXT, null],
        ];
    }

    #[DataProvider('filters')]
    public function test_filter_kind(AttributeInputType $type, ?string $expected): void
    {
        self::assertSame($expected, $type->filterKind());
    }

    /**
     * The exists: rule is the half of D-CAT-1 that actually holds — without it
     * a crafted request could post an attribute_value_id from another
     * category, or one that does not exist.
     */
    public function test_list_types_validate_against_the_attribute_values_table(): void
    {
        self::assertSame(
            ['integer', 'exists:attribute_values,id'],
            AttributeInputType::SELECT->valueRules(),
        );
        self::assertSame(
            AttributeInputType::SELECT->valueRules(),
            AttributeInputType::MULTISELECT->valueRules(),
        );
    }

    public function test_free_input_types_validate_their_own_shape(): void
    {
        self::assertSame(['string', 'max:255'], AttributeInputType::TEXT->valueRules());
        self::assertSame(['numeric'], AttributeInputType::NUMBER->valueRules());
        self::assertSame(['boolean'], AttributeInputType::BOOLEAN->valueRules());
    }

    public function test_every_case_answers_every_question(): void
    {
        foreach (AttributeInputType::cases() as $case) {
            self::assertIsBool($case->usesPlatformValueList());
            self::assertIsBool($case->allowsMultipleValues());
            self::assertIsBool($case->canDefineVariants());
            self::assertNotEmpty($case->valueRules(), $case->value);
            self::assertNotSame('', $case->label());
            $case->filterKind(); // must not throw UnhandledMatchError
        }
    }
}
