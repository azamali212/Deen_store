<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\ChangeTeamMemberRoleAction;
use App\Domain\Seller\Actions\InviteTeamMemberAction;
use App\Domain\Seller\Actions\ListSellerTeamAction;
use App\Domain\Seller\Actions\RemoveTeamMemberAction;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ChangeTeamMemberRoleRequest;
use App\Http\Requests\Seller\InviteTeamMemberRequest;
use App\Http\Resources\Seller\SellerTeamMemberResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Phase 7 — the owner builds their team. The store is always resolved
 * from $request->user()'s membership, never from an id in the URL.
 */
final class SellerTeamController extends Controller
{
    // Any active member can see who else is in the store
    public function index(
        Request $request,
        ListSellerTeamAction $action,
    ): AnonymousResourceCollection {

        return SellerTeamMemberResource::collection(
            $action->execute($request->user()->id),
        );
    }

    // Invite an EXISTING account as manager or staff (owner only)
    public function store(
        InviteTeamMemberRequest $request,
        InviteTeamMemberAction $action,
    ): SellerTeamMemberResource {

        return new SellerTeamMemberResource(
            $action->execute(
                $request->user()->id,
                $request->validated('email'),
                SellerTeamRole::from($request->validated('role')),
            ),
        );
    }

    // Change someone's role (owner only)
    public function update(
        ChangeTeamMemberRoleRequest $request,
        int $member,
        ChangeTeamMemberRoleAction $action,
    ): SellerTeamMemberResource {

        return new SellerTeamMemberResource(
            $action->execute(
                $request->user()->id,
                $member,
                SellerTeamRole::from($request->validated('role')),
            ),
        );
    }

    // Remove someone from the team (owner only)
    public function destroy(
        Request $request,
        int $member,
        RemoveTeamMemberAction $action,
    ): SellerTeamMemberResource {

        return new SellerTeamMemberResource(
            $action->execute($request->user()->id, $member),
        );
    }
}
