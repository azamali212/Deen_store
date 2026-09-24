<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\AcceptTeamInvitationAction;
use App\Domain\Seller\Actions\DeclineTeamInvitationAction;
use App\Domain\Seller\Actions\ListMyInvitationsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Seller\SellerTeamMemberResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * C21 — these endpoints sit OUTSIDE the seller-role group on purpose. The
 * invitee has no seller role yet; that is exactly what accepting gives
 * them. Auth alone is enough, and every lookup is scoped to the caller's
 * own user id, so nobody can act on someone else's invitation.
 */
final class SellerInvitationController extends Controller
{
    public function index(
        Request $request,
        ListMyInvitationsAction $action,
    ): AnonymousResourceCollection {

        return SellerTeamMemberResource::collection(
            $action->execute($request->user()->id),
        );
    }

    public function accept(
        Request $request,
        int $invitation,
        AcceptTeamInvitationAction $action,
    ): SellerTeamMemberResource {

        return new SellerTeamMemberResource(
            $action->execute($request->user()->id, $invitation),
        );
    }

    public function decline(
        Request $request,
        int $invitation,
        DeclineTeamInvitationAction $action,
    ): SellerTeamMemberResource {

        return new SellerTeamMemberResource(
            $action->execute($request->user()->id, $invitation),
        );
    }
}
