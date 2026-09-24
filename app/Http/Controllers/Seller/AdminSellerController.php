<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\DownloadBankProofAction;
use App\Domain\Seller\Actions\GetSellerDetailAction;
use App\Domain\Seller\Actions\ListSellersAction;
use App\Domain\Seller\Actions\ReactivateSellerAction;
use App\Domain\Seller\Actions\ReopenSellerStoreAction;
use App\Domain\Seller\Actions\ReviewStoreNameChangeAction;
use App\Domain\Seller\Actions\ReviewBankProofAction;
use App\Domain\Seller\Actions\SuspendSellerAction;
use App\Domain\Seller\DTO\ReviewBankProofDTO;
use App\Domain\Seller\DTO\SuspendSellerDTO;
use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ReviewStoreNameChangeRequest;
use App\Http\Requests\Seller\ReviewBankProofRequest;
use App\Http\Requests\Seller\SuspendSellerRequest;
use App\Http\Resources\Seller\AdminSellerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 6 — admin control of LIVE stores. The whole group sits behind
 * role:super_admin|platform_admin (D10).
 */
final class AdminSellerController extends Controller
{
    // ?status=active|suspended  ?bank=mismatch|pending_review|...
    public function index(
        Request $request,
        ListSellersAction $action,
    ): AnonymousResourceCollection {

        $validated = $request->validate([
            // Derived from the enum, not typed out: 'closed' (P8-5) was
            // added to the enum and this list did not follow, which made
            // closed stores impossible to filter for.
            'status' => ['nullable', 'in:'.implode(',', SellerProfileStatus::values())],
            'bank' => ['nullable', 'in:'.implode(',', BankVerificationStatus::values())],
            'name_pending' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return AdminSellerResource::collection(
            $action->execute(
                isset($validated['status']) ? SellerProfileStatus::from($validated['status']) : null,
                isset($validated['bank']) ? BankVerificationStatus::from($validated['bank']) : null,
                (int) ($validated['per_page'] ?? 20),
                (bool) ($validated['name_pending'] ?? false),
            ),
        );
    }

    public function show(
        int $seller,
        GetSellerDetailAction $action,
    ): AdminSellerResource {

        return new AdminSellerResource($action->execute($seller));
    }

    // Close the store (the account keeps working — P6-1)
    public function suspend(
        SuspendSellerRequest $request,
        int $seller,
        SuspendSellerAction $action,
    ): AdminSellerResource {

        return new AdminSellerResource(
            $action->execute(
                $seller,
                SuspendSellerDTO::fromArray($request->validated(), $request->user()->id),
            ),
        );
    }

    // P9-4 — approve or refuse a rename. The uniqueness check runs again
    // inside the transaction (C44): another store can take the name while
    // the request sits in the queue.
    public function reviewNameChange(
        int $seller,
        ReviewStoreNameChangeRequest $request,
        ReviewStoreNameChangeAction $action,
    ): AdminSellerResource {

        return new AdminSellerResource(
            $action->execute(
                $seller,
                (int) $request->user()->id,
                $request->approves(),
                $request->reason(),
            ),
        );
    }

    // P8-6 — grant a closed store's request to come back. NOT reactivate():
    // different columns, and the team's roles have to be handed back.
    public function reopen(
        Request $request,
        int $seller,
        ReopenSellerStoreAction $action,
    ): AdminSellerResource {

        return new AdminSellerResource(
            $action->execute($seller, $request->user()->id),
        );
    }

    public function reactivate(
        Request $request,
        int $seller,
        ReactivateSellerAction $action,
    ): AdminSellerResource {

        return new AdminSellerResource(
            $action->execute($seller, $request->user()->id),
        );
    }

    // Stream the bank statement the seller uploaded as proof
    public function downloadBankProof(
        int $seller,
        DownloadBankProofAction $action,
    ): StreamedResponse {

        return $action->execute($seller);
    }

    // verify -> admin_verified, reject -> back to mismatch (P6-2)
    public function reviewBankProof(
        ReviewBankProofRequest $request,
        int $seller,
        ReviewBankProofAction $action,
    ): AdminSellerResource {

        return new AdminSellerResource(
            $action->execute(
                $seller,
                ReviewBankProofDTO::fromArray($request->validated(), $request->user()->id),
            ),
        );
    }
}
