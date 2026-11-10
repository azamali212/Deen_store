<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * The life of one product_imports row — a seller's CSV upload (D-CAT-8).
 *
 * The one shape here that no other enum in this module has: a state that goes
 * BACKWARDS. failed -> queued is a retry, and it is the reason this is a
 * transition list rather than a one-way ladder.
 */
enum ImportStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case DONE = 'done';
    case FAILED = 'failed';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, self>
     *
     * WHY failed -> queued EXISTS AND done -> queued DOES NOT:
     * a failed import imported nothing, so running it again is harmless. A
     * finished import already created products, and running the same file
     * again would create them a second time — the seller ends up with 4,812
     * duplicates and no way to tell which set is real. Re-importing a
     * finished file must be a NEW import row, so that both runs and both
     * error reports survive.
     *
     * There is no running -> queued either. A job that is halfway through a
     * file has already written rows; sending it back to the start would
     * re-process those. A stuck job is FAILED first, and the retry is then a
     * normal failed -> queued.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::QUEUED => [self::RUNNING],
            self::RUNNING => [self::DONE, self::FAILED],
            self::DONE => [],
            self::FAILED => [self::QUEUED],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }

    /** True once the job is over, whichever way it went. */
    public function isFinished(): bool
    {
        return $this === self::DONE || $this === self::FAILED;
    }

    /** True only for the one state the seller may press "try again" on. */
    public function isRetryable(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * The two states that mean "this seller already has an import on the go",
     * as STRINGS for a whereIn().
     *
     * @return array<int, string>
     *
     * Used to refuse a second upload while one is still running — two jobs
     * reading two files for one seller will fight over the same SKUs and the
     * loser's rows are lost with no error anywhere.
     *
     * array_map($fn, $list) is the new function here. Where array_filter KEEPS
     * OR DROPS each item, array_map REPLACES each item with whatever the
     * closure returns — same number of items out as went in, in the same
     * order. So four cases in, four strings out.
     *
     *     fn (self $s): string => $s->value
     *
     * `fn` is PHP's short closure: one expression, and its value is returned
     * automatically with no `return` keyword. Note it CANNOT be replaced by
     * array_column() here: array_column reads a property out of a plain array
     * or object, and works on self::cases() only because enum cases expose
     * `value` directly. Once the list has been filtered by a method call,
     * array_map with a closure is the tool that fits.
     */
    public static function inProgressValues(): array
    {
        $open = array_filter(self::cases(), fn (self $s): bool => ! $s->isFinished());

        return array_values(array_map(fn (self $s): string => $s->value, $open));
    }

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Waiting to start',
            self::RUNNING => 'Importing',
            self::DONE => 'Finished',
            self::FAILED => 'Failed',
        };
    }
}
