<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * How a shopper arrived at a product page. One column on product_views.
 *
 * This enum is different from every other one in this module in one important
 * way: ITS VALUE COMES FROM THE OUTSIDE WORLD. Every other enum here is set by
 * our own code, but this one is read off a request — ?src=search — which means
 * anyone can send anything, including an empty string, a 2 MB string, or
 * 'DROP TABLE'. That is what fromRequest() exists for, and why nothing else
 * in Catalog is allowed to build a ViewSource straight from input.
 */
enum ViewSource: string
{
    case BROWSE = 'browse';
    case SEARCH = 'search';
    case DIRECT = 'direct';
    case RECOMMENDATION = 'recommendation';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Turn whatever the request carried into a real case — safely.
     *
     * THE TWO WAYS PHP TURNS A STRING INTO AN ENUM, AND WHY ONLY ONE IS SAFE
     * HERE:
     *   ProductStatus::from('draft')   -> the case. Unknown string -> THROWS
     *                                     a ValueError.
     *   ProductStatus::tryFrom('xyz')  -> null instead of throwing.
     *
     * from() is right where the string came from OUR OWN database, because
     * there an unknown value means the data is corrupt and silence would be
     * worse than a crash. Here the string came from a stranger's browser, and
     * a crash is a 500 on the product page — so a bad ?src= would let anyone
     * break the page for everyone simply by typing in the address bar.
     *
     * `??` is the null-coalescing operator: "left side, unless it is null, in
     * which case the right side". So an unknown, misspelled, empty or missing
     * source is recorded as DIRECT, which is the honest answer — we do not
     * know where they came from.
     *
     * The ?string parameter type is there because a missing query string is
     * null, not an empty string, and tryFrom(null) would be a TypeError.
     */
    public static function fromRequest(?string $raw): self
    {
        if ($raw === null) {
            return self::DIRECT;
        }

        return self::tryFrom($raw) ?? self::DIRECT;
    }

    /**
     * True when counting this view as evidence would teach the model what it
     * already believes.
     *
     * A view that happened BECAUSE we recommended the product is not
     * independent evidence that the product is good — it is evidence that we
     * showed it. Train on those rows equally and the model recommends what it
     * already recommends, popular products get more popular because they are
     * popular, and a genuinely better product that was never shown can never
     * climb. That is a feedback loop, and it is the standard failure of a
     * recommender trained on its own output.
     *
     * So the training export (a later domain, not Catalog) has to be able to
     * separate these rows out or weigh them down. Catalog's only job is to
     * record the source honestly so that choice stays available — which is
     * exactly why product_views stores a SOURCE at all instead of just
     * counting views.
     */
    public function createsFeedbackLoop(): bool
    {
        return $this === self::RECOMMENDATION;
    }

    public function label(): string
    {
        return match ($this) {
            self::BROWSE => 'Category browsing',
            self::SEARCH => 'Search results',
            self::DIRECT => 'Direct link',
            self::RECOMMENDATION => 'Recommended',
        };
    }
}
