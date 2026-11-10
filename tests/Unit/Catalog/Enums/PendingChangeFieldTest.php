<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\PendingChangeField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PendingChangeFieldTest extends TestCase
{
    public function test_values_are_derived_from_the_cases(): void
    {
        self::assertSame(
            ['name', 'description', 'media', 'brand_name', 'category', 'price', 'stock', 'variants'],
            PendingChangeField::values(),
        );
    }

    public static function approvalRules(): array
    {
        return [
            'name waits' => [PendingChangeField::NAME, true],
            'description waits' => [PendingChangeField::DESCRIPTION, true],
            'media waits' => [PendingChangeField::MEDIA, true],
            'brand name waits' => [PendingChangeField::BRAND_NAME, true],
            'category is immediate but leaves LIVE' => [PendingChangeField::CATEGORY, false],
            'price is immediate' => [PendingChangeField::PRICE, false],
            'stock is immediate' => [PendingChangeField::STOCK, false],
            'variants are immediate' => [PendingChangeField::VARIANTS, false],
        ];
    }

    #[DataProvider('approvalRules')]
    public function test_which_fields_wait_for_approval(PendingChangeField $field, bool $waits): void
    {
        self::assertSame($waits, $field->requiresApproval());
    }

    /**
     * Price and stock are the seller's daily work. Holding them for review
     * would make the platform unusable, and neither one can carry a
     * counterfeit or a banned image.
     */
    public function test_the_sellers_daily_fields_are_never_held(): void
    {
        self::assertFalse(PendingChangeField::PRICE->requiresApproval());
        self::assertFalse(PendingChangeField::STOCK->requiresApproval());
    }

    /** Section 3: brand_name is the door counterfeits come through. */
    public function test_only_brand_name_forces_a_human_admin(): void
    {
        self::assertTrue(PendingChangeField::BRAND_NAME->approverIsAdminOnly());

        foreach (PendingChangeField::cases() as $case) {
            if ($case !== PendingChangeField::BRAND_NAME) {
                self::assertFalse($case->approverIsAdminOnly(), $case->value);
            }
        }
    }

    /**
     * The other half of ProductStatus::allowedTransitions(): LIVE lists DRAFT
     * only because of this one field.
     */
    public function test_only_a_category_change_sends_the_product_back_to_draft(): void
    {
        self::assertTrue(PendingChangeField::CATEGORY->sendsProductBackToDraft());

        foreach (PendingChangeField::cases() as $case) {
            if ($case !== PendingChangeField::CATEGORY) {
                self::assertFalse($case->sendsProductBackToDraft(), $case->value);
            }
        }
    }

    /**
     * A field can never both wait for approval AND knock the product out of
     * LIVE — the first keeps the old version live, the second takes it down.
     * Together they would mean a product that is live and not live at once.
     */
    public function test_no_field_both_waits_and_unpublishes(): void
    {
        foreach (PendingChangeField::cases() as $case) {
            self::assertFalse(
                $case->requiresApproval() && $case->sendsProductBackToDraft(),
                $case->value . ' cannot be both pending and back-to-draft',
            );
        }
    }

    public function test_requiring_approval_is_derived_and_correctly_keyed(): void
    {
        $fields = PendingChangeField::requiringApproval();

        self::assertSame([
            PendingChangeField::NAME,
            PendingChangeField::DESCRIPTION,
            PendingChangeField::MEDIA,
            PendingChangeField::BRAND_NAME,
        ], $fields);

        // array_values did its job: a real list, 0..3, so json_encode gives an
        // array and not an object.
        self::assertSame([0, 1, 2, 3], array_keys($fields));

        // and it agrees with the per-case method, so the two can never drift
        foreach (PendingChangeField::cases() as $case) {
            self::assertSame(
                $case->requiresApproval(),
                in_array($case, $fields, strict: true),
                $case->value,
            );
        }
    }

    public function test_every_case_answers_every_question(): void
    {
        foreach (PendingChangeField::cases() as $case) {
            self::assertIsBool($case->requiresApproval());
            self::assertIsBool($case->approverIsAdminOnly());
            self::assertIsBool($case->sendsProductBackToDraft());
            self::assertNotSame('', $case->label());
        }
    }
}
