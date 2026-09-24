<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerTeamMemberJoined;
use App\Domain\Seller\Notifications\SellerTeamMemberJoinedNotification;

final class NotifyOwnerOfTeamJoinListener
{
    public function handle(SellerTeamMemberJoined $event): void
    {
        $member = $event->member->loadMissing(['user', 'sellerProfile.user']);

        $member->sellerProfile->user->notify(new SellerTeamMemberJoinedNotification(
            storeName: $member->sellerProfile->store_name,
            memberName: (string) $member->user->name,
            memberEmail: (string) $member->user->email,
            roleLabel: $member->role->label(),
        ));
    }
}
