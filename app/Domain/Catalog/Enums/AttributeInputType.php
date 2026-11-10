<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * How an attribute is filled in, which decides four separate things: whether
 * its values come from the platform list (D-CAT-1), whether it may carry more
 * than one value, whether it can be a variant axis, and how the shopper
 * filters on it.
 *
 * All four hang off this one column (attributes.input_type), so all four
 * answers belong here rather than being re-derived in a service, a form
 * request and a blade file.
 */
enum AttributeInputType: string
{
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case TEXT = 'text';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * True when the seller PICKS from attribute_values and never types.
     *
     * This is D-CAT-1 in one method. Free text is a one-way door: "Red",
     * "red", "RED" and "Laal" become four values and the filters break on the
     * day the data is already dirty. So the two list types read their options
     * out of attribute_values, and the other three store the seller's own
     * input on the pivot row.
     */
    public function usesPlatformValueList(): bool
    {
        return $this === self::SELECT || $this === self::MULTISELECT;
    }

    /**
     * True when one product may carry several values for this one attribute.
     *
     * Only ever legal on product_attribute_values. On a VARIANT it is
     * impossible by construction: section 5 puts
     * unique(product_variant_id, attribute_id) on variant_attribute_values,
     * so the database itself refuses a second value. That unique is also why
     * the pivot stores attribute_id and not only attribute_value_id.
     */
    public function allowsMultipleValues(): bool
    {
        return $this === self::MULTISELECT;
    }

    /**
     * True when this attribute may be used as a VARIANT AXIS — the thing that
     * makes "Blue / M" a different buyable row from "Blue / L".
     *
     * Only SELECT qualifies, and each of the other four fails for its own
     * reason:
     *   MULTISELECT  a variant must sit at exactly ONE point on each axis.
     *   TEXT         free text cannot be an axis; two sellers typing "Med"
     *                and "Medium" would produce two axes meaning one thing.
     *   NUMBER       a number is a measurement of the variant, not a choice
     *                the shopper makes between variants.
     *   BOOLEAN      a yes/no axis produces at most two variants and is
     *                really a property of the product.
     */
    public function canDefineVariants(): bool
    {
        return $this === self::SELECT;
    }

    /**
     * Which control the shopper's filter sidebar draws for this attribute, or
     * null when it is not filterable at all.
     *
     * Note the return type `?string` — the question mark means "a string, OR
     * null". TEXT genuinely has no answer here: a free-text attribute cannot
     * be filtered, because a filter needs a fixed set of things to tick and
     * free text has none. Returning null says that out loud. Returning an
     * empty string or the word 'none' would force every caller to remember
     * which magic value means nothing.
     */
    public function filterKind(): ?string
    {
        return match ($this) {
            self::SELECT, self::MULTISELECT => 'chips',
            self::BOOLEAN => 'toggle',
            self::NUMBER => 'range',
            self::TEXT => null,
        };
    }

    /**
     * The validation rules for ONE submitted value of this attribute.
     *
     * @return array<int, string>
     *
     * The form request builds the final rule from two pieces: these rules, and
     * allowsMultipleValues() which says whether to wrap them. So a SELECT
     * becomes
     *     'attributes.7'   => ['integer', 'exists:attribute_values,id']
     * and a MULTISELECT becomes
     *     'attributes.7'   => ['array'],
     *     'attributes.7.*' => ['integer', 'exists:attribute_values,id']
     *
     * `exists:attribute_values,id` is the second half of D-CAT-1's guarantee
     * and the half that actually holds: the enum says values come from the
     * platform list, and this rule is what makes a crafted request carrying
     * an id that is not in that table fail.
     */
    public function valueRules(): array
    {
        return match ($this) {
            self::SELECT, self::MULTISELECT => ['integer', 'exists:attribute_values,id'],
            self::TEXT => ['string', 'max:255'],
            self::NUMBER => ['numeric'],
            self::BOOLEAN => ['boolean'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SELECT => 'Pick one',
            self::MULTISELECT => 'Pick several',
            self::TEXT => 'Short text',
            self::NUMBER => 'Number',
            self::BOOLEAN => 'Yes / No',
        };
    }
}
