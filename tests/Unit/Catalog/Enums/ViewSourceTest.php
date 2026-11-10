<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\ViewSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewSourceTest extends TestCase
{
    public function test_values_are_derived_from_the_cases(): void
    {
        self::assertSame(
            ['browse', 'search', 'direct', 'recommendation'],
            ViewSource::values(),
        );
    }

    public static function requestInput(): array
    {
        return [
            'a known source' => ['search', ViewSource::SEARCH],
            'another known source' => ['recommendation', ViewSource::RECOMMENDATION],
            'nothing in the query string' => [null, ViewSource::DIRECT],
            'an empty string' => ['', ViewSource::DIRECT],
            'a misspelling' => ['serach', ViewSource::DIRECT],
            'the wrong case' => ['SEARCH', ViewSource::DIRECT],
            'whitespace' => [' search ', ViewSource::DIRECT],
            'an injection attempt' => ["browse'; DROP TABLE products; --", ViewSource::DIRECT],
            'a very long string' => [str_repeat('a', 5000), ViewSource::DIRECT],
        ];
    }

    /**
     * This is the only enum in Catalog built from outside input, so every one
     * of these has to land on a real case rather than throw. A ValueError here
     * would be a 500 on the product page that anyone could trigger from the
     * address bar.
     */
    #[DataProvider('requestInput')]
    public function test_from_request_never_throws_and_always_returns_a_case(
        ?string $raw,
        ViewSource $expected,
    ): void {
        self::assertSame($expected, ViewSource::fromRequest($raw));
    }

    public function test_every_valid_value_round_trips_through_from_request(): void
    {
        foreach (ViewSource::cases() as $case) {
            self::assertSame($case, ViewSource::fromRequest($case->value));
        }
    }

    /**
     * A view we caused by recommending the product is not independent evidence
     * that the product is good. Catalog's job is only to record the source so a
     * later training export can separate these rows out.
     */
    public function test_only_recommendation_creates_a_feedback_loop(): void
    {
        self::assertTrue(ViewSource::RECOMMENDATION->createsFeedbackLoop());

        foreach (ViewSource::cases() as $case) {
            if ($case !== ViewSource::RECOMMENDATION) {
                self::assertFalse($case->createsFeedbackLoop(), $case->value);
            }
        }
    }

    public function test_every_case_answers_every_question(): void
    {
        foreach (ViewSource::cases() as $case) {
            self::assertIsBool($case->createsFeedbackLoop());
            self::assertNotSame('', $case->label());
        }
    }
}
