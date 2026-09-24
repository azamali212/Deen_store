<?php

declare(strict_types=1);

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\DTO\AuditContextDTO;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use App\Domain\Audit\Jobs\WriteAuditLogJob;
use App\Domain\Seller\Events\SellerTeamMemberInvited;
use App\Domain\Seller\Events\SellerTeamMemberJoined;
use App\Domain\Seller\Events\SellerTeamMemberRemoved;
use App\Domain\Seller\Events\SellerTeamRoleChanged;
use App\Models\User;
use LogicException;

/**
 * Phase 7 audit. The SUBJECT is the team member the action was about —
 * the ACTOR (who did it) comes from the request context, which is exactly
 * the accountability a shared password never gave us.
 */
final class AuditSellerTeamListener
{
    public function handle(
        SellerTeamMemberInvited|SellerTeamMemberJoined|SellerTeamRoleChanged|SellerTeamMemberRemoved $event,
    ): void {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        $member = $event->member;

        [$action, $severity, $description, $extra] = match (true) {
            $event instanceof SellerTeamMemberInvited => [
                AuditAction::SELLER_TEAM_MEMBER_INVITED,
                AuditSeverity::INFO,
                'A user was invited to a seller store team.',
                ['invited_by' => $member->invited_by],
            ],
            $event instanceof SellerTeamMemberJoined => [
                AuditAction::SELLER_TEAM_MEMBER_JOINED,
                AuditSeverity::NOTICE,
                'A user accepted a store invitation and now has access.',
                [],
            ],
            $event instanceof SellerTeamRoleChanged => [
                AuditAction::SELLER_TEAM_ROLE_CHANGED,
                AuditSeverity::NOTICE,
                'A team member role was changed.',
                ['previous_role' => $event->previousRole->value],
            ],
            $event instanceof SellerTeamMemberRemoved => [
                AuditAction::SELLER_TEAM_MEMBER_REMOVED,
                AuditSeverity::NOTICE,
                $event->declinedBySelf
                    ? 'A user declined a store invitation.'
                    : 'A team member was removed from a store.',
                ['declined_by_self' => $event->declinedBySelf],
            ],
            default => throw new LogicException('Unsupported event: '.$event::class),
        };

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: $action,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: $severity,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $member->user_id,
                description: $description,
                newValues: [
                    'seller_profile_id' => $member->seller_profile_id,
                    'team_member_id' => $member->id,
                    'role' => $member->role->value,
                    'status' => $member->status->value,
                ] + $extra,
                occurredAt: now(),
            ),
        );
    }
}
