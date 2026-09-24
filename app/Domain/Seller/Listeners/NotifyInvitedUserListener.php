<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerTeamMemberInvited;
use App\Domain\Seller\Notifications\SellerTeamInvitationNotification;

final class NotifyInvitedUserListener
{
    public function handle(SellerTeamMemberInvited $event): void
    {
        $member = $event->member->loadMissing(['user', 'sellerProfile', 'invitedBy']);

        $member->user->notify(new SellerTeamInvitationNotification(
            memberId: $member->id,
            storeName: $member->sellerProfile->store_name,
            roleLabel: $member->role->label(),
            invitedByName: (string) ($member->invitedBy?->name ?? 'The store owner'),
        ));
    }
}
