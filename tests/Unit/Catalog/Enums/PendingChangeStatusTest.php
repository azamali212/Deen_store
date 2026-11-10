<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\PendingChangeStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PendingChangeStatusTest extends TestCase
{
    public function test_values_are_derived_from_the_cases(): void
    {
        self::assertSame(['pending', 'applied', 'rejected'], PendingChangeStatus::values());
    }

    public function test_only_pending_is_open(): void
    {
        self::assertTrue(PendingChangeStatus::PENDING->isOpen());
        self::assertFalse(PendingChangeStatus::APPLIED->isOpen());
        self::assertFalse(PendingChangeStatus::REJECTED->isOpen());
    }

    public static function transitions(): array
    {
        return [
            'pending can be applied' => [PendingChangeStatus::PENDING, PendingChangeStatus::APPLIED, true],
            'pending can be rejected' => [PendingChangeStatus::PENDING, PendingChangeStatus::REJECTED, true],
            'pending cannot go to itself' => [PendingChangeStatus::PENDING, PendingChangeStatus::PENDING, false],
            'applied is terminal' => [PendingChangeStatus::APPLIED, PendingChangeStatus::PENDING, false],
            'applied cannot be rejected later' => [PendingChangeStatus::APPLIED, PendingChangeStatus::REJECTED, false],
            'rejected is terminal' => [PendingChangeStatus::REJECTED, PendingChangeStatus::PENDING, false],
            'rejected cannot be applied later' => [PendingChangeStatus::REJECTED, PendingChangeStatus::APPLIED, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_transitions(
        PendingChangeStatus $from,
        PendingChangeStatus $to,
        bool $allowed,
    ): void {
        self::assertSame($allowed, $from->canTransitionTo($to));
    }

    /**
     * A decided change is a historical fact. Re-deciding one would rewrite the
     * record instead of adding to it, and the seller's second attempt would
     * be indistinguishable from the first.
     */
    public function test_a_decided_change_can_never_move_again(): void
    {
        foreach ([PendingChangeStatus::APPLIED, PendingChangeStatus::REJECTED] as $final) {
            self::assertSame([], $final->allowedTransitions());

            foreach (PendingChangeStatus::cases() as $target) {
                self::assertFalse($final->canTransitionTo($target));
            }
        }
    }

    public function test_no_status_can_transition_to_itself(): void
    {
        foreach (PendingChangeStatus::cases() as $case) {
            self::assertFalse($case->canTransitionTo($case), $case->value);
        }
    }

    public function test_every_case_answers_every_question(): void
    {
        foreach (PendingChangeStatus::cases() as $case) {
            self::assertIsBool($case->isOpen());
            self::assertIsArray($case->allowedTransitions());
            self::assertNotSame('', $case->label());
        }
    }
}
