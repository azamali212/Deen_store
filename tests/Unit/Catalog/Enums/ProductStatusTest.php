<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\ProductStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductStatusTest extends TestCase
{
    public function test_values_returns_every_case_and_is_not_hand_written(): void
    {
        $this->assertSame(
            ['draft', 'pending', 'live', 'rejected', 'archived'],
            ProductStatus::values(),
        );

        // The real point of values(): it can never fall behind the cases.
        $this->assertCount(count(ProductStatus::cases()), ProductStatus::values());
    }

    public function test_only_live_is_visible_to_shoppers(): void
    {
        foreach (ProductStatus::cases() as $status) {
            $this->assertSame(
                $status === ProductStatus::LIVE,
                $status->isVisibleToShoppers(),
                $status->value.' reported the wrong visibility',
            );
        }
    }

    public function test_only_draft_and_rejected_are_freely_editable(): void
    {
        $this->assertTrue(ProductStatus::DRAFT->isEditable());
        $this->assertTrue(ProductStatus::REJECTED->isEditable());

        $this->assertFalse(ProductStatus::PENDING->isEditable());
        $this->assertFalse(ProductStatus::ARCHIVED->isEditable());

        // LIVE is editable, but only through the edit gate (D-CAT-6) — so
        // this method must say false. See the docblock on isEditable().
        $this->assertFalse(ProductStatus::LIVE->isEditable());
    }

    /**
     * @return array<string, array{ProductStatus, ProductStatus, bool}>
     */
    public static function transitions(): array
    {
        return [
            // the three ways submit can end
            'draft -> pending'    => [ProductStatus::DRAFT, ProductStatus::PENDING, true],
            'draft -> live'       => [ProductStatus::DRAFT, ProductStatus::LIVE, true],
            'draft -> rejected'   => [ProductStatus::DRAFT, ProductStatus::REJECTED, true],
            'draft -> archived'   => [ProductStatus::DRAFT, ProductStatus::ARCHIVED, true],

            // admin resolves, or the seller withdraws
            'pending -> live'     => [ProductStatus::PENDING, ProductStatus::LIVE, true],
            'pending -> rejected' => [ProductStatus::PENDING, ProductStatus::REJECTED, true],
            'pending -> draft'    => [ProductStatus::PENDING, ProductStatus::DRAFT, true],

            // category change, and retiring a product
            'live -> draft'       => [ProductStatus::LIVE, ProductStatus::DRAFT, true],
            'live -> archived'    => [ProductStatus::LIVE, ProductStatus::ARCHIVED, true],

            'rejected -> draft'   => [ProductStatus::REJECTED, ProductStatus::DRAFT, true],
            'archived -> draft'   => [ProductStatus::ARCHIVED, ProductStatus::DRAFT, true],

            // the one everybody reaches for, and the reason it is refused
            'live -> pending is refused' => [ProductStatus::LIVE, ProductStatus::PENDING, false],

            // a rejected product cannot sneak straight onto the shop
            'rejected -> live is refused' => [ProductStatus::REJECTED, ProductStatus::LIVE, false],

            // an archived product must re-pass the gate
            'archived -> live is refused' => [ProductStatus::ARCHIVED, ProductStatus::LIVE, false],

            // no state may move to itself
            'live -> live is refused'   => [ProductStatus::LIVE, ProductStatus::LIVE, false],
            'draft -> draft is refused' => [ProductStatus::DRAFT, ProductStatus::DRAFT, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_transition_rules(ProductStatus $from, ProductStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }

    public function test_every_case_has_a_label(): void
    {
        foreach (ProductStatus::cases() as $status) {
            $this->assertNotSame('', $status->label(), $status->value.' has no label');
        }
    }
}
