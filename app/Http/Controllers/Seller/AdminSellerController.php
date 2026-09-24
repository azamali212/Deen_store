<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\DownloadBankProofAction;
use App\Domain\Seller\Actions\GetSellerDetailAction;
use App\Domain\Seller\Actions\ListSellersAction;
use App\Domain\Seller\Actions\ReactivateSellerAction;
use App\Domain\Seller\Actions\ReviewBankProofAction;
use App\Domain\Seller\Actions\SuspendSellerAction;
use App\Domain\Seller\DTO\ReviewBankProofDTO;
use App\Domain\Seller\DTO\SuspendSellerDTO;
use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Http\Controllers\Controller;
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
            'status' => ['nullable', 'in:active,suspended'],
            'bank' => ['nullable', 'in:'.implode(',', BankVerificationStatus::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return AdminSellerResource::collection(
            $action->execute(
                isset($validated['status']) ? SellerProfileStatus::from($validated['status']) : null,
                isset($validated['bank']) ? BankVerificationStatus::from($validated['bank']) : null,
                (int) ($validated['per_page'] ?? 20),
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
