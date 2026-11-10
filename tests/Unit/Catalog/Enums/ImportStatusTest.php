<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\ImportStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ImportStatusTest extends TestCase
{
    public function test_values_are_derived_from_the_cases(): void
    {
        self::assertSame(['queued', 'running', 'done', 'failed'], ImportStatus::values());
    }

    public static function transitions(): array
    {
        return [
            'queued starts' => [ImportStatus::QUEUED, ImportStatus::RUNNING, true],
            'queued cannot finish without running' => [ImportStatus::QUEUED, ImportStatus::DONE, false],
            'running can finish' => [ImportStatus::RUNNING, ImportStatus::DONE, true],
            'running can fail' => [ImportStatus::RUNNING, ImportStatus::FAILED, true],
            'running cannot be re-queued' => [ImportStatus::RUNNING, ImportStatus::QUEUED, false],
            'failed can be retried' => [ImportStatus::FAILED, ImportStatus::QUEUED, true],
            'failed cannot jump straight to running' => [ImportStatus::FAILED, ImportStatus::RUNNING, false],
            'done is terminal' => [ImportStatus::DONE, ImportStatus::QUEUED, false],
            'done cannot fail afterwards' => [ImportStatus::DONE, ImportStatus::FAILED, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_transitions(ImportStatus $from, ImportStatus $to, bool $allowed): void
    {
        self::assertSame($allowed, $from->canTransitionTo($to));
    }

    /**
     * The one asymmetry worth a test of its own: a failed import wrote nothing
     * so it may be re-run, a finished one already created products so re-running
     * the same file would duplicate all of them.
     */
    public function test_only_a_failed_import_may_be_re_queued(): void
    {
        self::assertTrue(ImportStatus::FAILED->canTransitionTo(ImportStatus::QUEUED));
        self::assertFalse(ImportStatus::DONE->canTransitionTo(ImportStatus::QUEUED));
        self::assertTrue(ImportStatus::FAILED->isRetryable());
        self::assertFalse(ImportStatus::DONE->isRetryable());
    }

    public function test_finished_covers_both_endings(): void
    {
        self::assertTrue(ImportStatus::DONE->isFinished());
        self::assertTrue(ImportStatus::FAILED->isFinished());
        self::assertFalse(ImportStatus::QUEUED->isFinished());
        self::assertFalse(ImportStatus::RUNNING->isFinished());
    }

    public function test_in_progress_values_are_strings_for_a_where_in(): void
    {
        $open = ImportStatus::inProgressValues();

        self::assertSame(['queued', 'running'], $open);
        self::assertSame([0, 1], array_keys($open));

        foreach ($open as $value) {
            self::assertIsString($value);
        }
    }

    /** The derived list and the per-case method must never disagree. */
    public function test_in_progress_values_agree_with_is_finished(): void
    {
        $open = ImportStatus::inProgressValues();

        foreach (ImportStatus::cases() as $case) {
            self::assertSame(! $case->isFinished(), in_array($case->value, $open, strict: true), $case->value);
        }
    }

    public function test_no_status_can_transition_to_itself(): void
    {
        foreach (ImportStatus::cases() as $case) {
            self::assertFalse($case->canTransitionTo($case), $case->value);
        }
    }

    public function test_every_case_answers_every_question(): void
    {
        foreach (ImportStatus::cases() as $case) {
            self::assertIsArray($case->allowedTransitions());
            self::assertIsBool($case->isFinished());
            self::assertIsBool($case->isRetryable());
            self::assertNotSame('', $case->label());
        }
    }
}
