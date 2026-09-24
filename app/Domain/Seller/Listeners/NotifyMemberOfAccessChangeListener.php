<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerTeamMemberRemoved;
use App\Domain\Seller\Events\SellerTeamRoleChanged;
use App\Domain\Seller\Notifications\SellerTeamAccessChangedNotification;

final class NotifyMemberOfAccessChangeListener
{
    public function handle(SellerTeamRoleChanged|SellerTeamMemberRemoved $event): void
    {
        // Somebody who declined their own invitation does not need an
        // email telling them they declined it.
        if ($event instanceof SellerTeamMemberRemoved && $event->declinedBySelf) {
            return;
        }

        $member = $event->member->loadMissing(['user', 'sellerProfile']);

        $member->user->notify(new SellerTeamAccessChangedNotification(
            storeName: $member->sellerProfile->store_name,
            removed: $event instanceof SellerTeamMemberRemoved,
            newRoleLabel: $event instanceof SellerTeamRoleChanged ? $member->role->label() : null,
        ));
    }
}
