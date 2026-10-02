<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\Auth\Services\PanelAccessService;
use App\Domain\Seller\Services\SellerTeamService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\Seller\SellerContextResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A1 — "who am I, and what may I do right now".
 *
 * Deliberately NOT folded into the login response. Two reasons:
 *
 *  1. It goes stale. An owner can promote a staff member to manager while
 *     that person is logged in; their token stays valid but their powers
 *     change. Baked into the login response, they would see the old UI
 *     until they logged out and back in. Here the client just re-reads it.
 *
 *  2. Layering. The Auth domain would otherwise have to know about the
 *     Seller domain to build its own response.
 */
final class MeController extends Controller
{
    public function __construct(
        private readonly PanelAccessService $panels,
        private readonly SellerTeamService $team,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $membership = $this->team->contextFor((int) $user->id);

        return response()->json([
            'success' => true,
            'data' => [
                // Carries roles and permissions already.
                'user' => new UserResource($user),

                'accessible_panels' => $this->panels->accessiblePanels($user),

                // null for everyone who is not in a store — which is most
                // customers, and that is the honest answer.
                'seller' => $membership !== null
                    ? new SellerContextResource($membership->loadMissing('sellerProfile'))
                    : null,
            ],
        ]);
    }
}
