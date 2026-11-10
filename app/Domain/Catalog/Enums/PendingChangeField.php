<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * Every field class a seller can edit on a LIVE product, and what happens when
 * they do. This is D-CAT-6's edit gate, written down in one place.
 *
 * WHY FIELDS THAT NEVER BECOME PENDING ARE LISTED HERE TOO (price, stock,
 * variants, category):
 * the edit gate's real job is to CLASSIFY an incoming field into one of four
 * behaviours. If this enum held only the fields that wait for approval, the
 * service would need a second, hand-typed list of the ones that do not — and
 * that is exactly the stale-list problem ProductStatus::values() exists to
 * prevent, except now it is a security list. A field missing from a
 * hand-typed "immediate" list would quietly get the default behaviour, and
 * whichever default is chosen is wrong for half the fields.
 * So: every editable field is a case, and the methods below say what it does.
 *
 * OWNER DECISION STILL OPEN: if you would rather this enum carried only the
 * four pending fields, the other four need a named home (an EditableField
 * enum) before C8. Say which and it goes in the blueprint as D-CAT-9.
 */
enum PendingChangeField: string
{
    case NAME = 'name';
    case DESCRIPTION = 'description';
    case MEDIA = 'media';
    case BRAND_NAME = 'brand_name';
    case CATEGORY = 'category';
    case PRICE = 'price';
    case STOCK = 'stock';
    case VARIANTS = 'variants';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * True when the edit does NOT go live — it is written to
     * product_pending_changes and the OLD value stays live until it is
     * approved (D-CAT-6's key rule).
     *
     * These four are exactly the fields a counterfeiter or a scammer would
     * swap AFTER approval: list a clean kettle, get it live, then change the
     * images and the title to something else entirely. The first version of
     * this design guarded create only, which left that door wide open and
     * legal. Amazon scans detail-page CHANGES for the same reason.
     *
     * There is deliberately NO isAppliedImmediately() method next to this one.
     * At decision time a change either waits or it does not, so a second
     * method would be `! requiresApproval()` for ever — two places to change
     * one rule. Contrast FulfilmentType, where both questions ARE written out
     * because a future third case can answer yes to both.
     */
    public function requiresApproval(): bool
    {
        return match ($this) {
            self::NAME, self::DESCRIPTION, self::MEDIA, self::BRAND_NAME => true,
            self::CATEGORY, self::PRICE, self::STOCK, self::VARIANTS => false,
        };
    }

    /**
     * True when AI and moderation may NOT decide this on their own, however
     * confident they are — a human admin has to.
     *
     * brand_name only. This single field is the door counterfeits come
     * through: everything else about a fake can be honest while the brand
     * name is a lie, and no image scan catches a real photo of a real-looking
     * box. It is also the field with legal consequences for the platform, not
     * just for the seller, which is why it is the one place the create gate's
     * "no human approvals" rule is overridden on purpose.
     */
    public function approverIsAdminOnly(): bool
    {
        return $this === self::BRAND_NAME;
    }

    /**
     * True when this edit takes the product out of LIVE and back to DRAFT.
     *
     * Only a category change. A new category means a different set of allowed
     * attributes, so the existing variants are built on axes the product no
     * longer has. Such a product cannot stay in the shop: a shopper would
     * order a variant that no longer means anything.
     *
     * This is the other end of ProductStatus: LIVE's allowedTransitions() list
     * contains DRAFT for exactly this one reason, and the comment there says
     * so. Two files, one rule, and each points at the other.
     */
    public function sendsProductBackToDraft(): bool
    {
        return $this === self::CATEGORY;
    }

    /**
     * The fields that actually create a product_pending_changes row.
     *
     * @return array<int, self>
     *
     * DERIVED, never hand-written — same principle as values(), and here it
     * matters more because the list is a security boundary.
     *
     * Two functions do the work:
     *   array_filter($list, $test)  keeps only the items for which $test
     *                               returns true. The second argument is a
     *                               CLOSURE — a small unnamed function
     *                               written inline. PHP calls it once per
     *                               item, handing it that item.
     *   array_values($list)         renumbers the keys 0,1,2...
     *
     * array_values is not decoration. array_filter KEEPS THE ORIGINAL KEYS,
     * so filtering cases 0..7 down to the first four leaves keys 0,1,2,3 —
     * but filter out the first case instead and the keys are 1,2,3, with no
     * key 0. json_encode then writes that as an OBJECT {"1":...} instead of
     * an ARRAY [...], and a JavaScript client that did `data.forEach` gets
     * nothing with no error. array_values is what keeps this a list.
     */
    public static function requiringApproval(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $field): bool => $field->requiresApproval(),
        ));
    }

    public function label(): string
    {
        return match ($this) {
            self::NAME => 'Product name',
            self::DESCRIPTION => 'Description',
            self::MEDIA => 'Photos and videos',
            self::BRAND_NAME => 'Brand',
            self::CATEGORY => 'Category',
            self::PRICE => 'Price',
            self::STOCK => 'Stock',
            self::VARIANTS => 'Variants',
        };
    }
}
