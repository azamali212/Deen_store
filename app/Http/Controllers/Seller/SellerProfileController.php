<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\CloseSellerStoreAction;
use App\Domain\Seller\Actions\DeleteSellerLogoAction;
use App\Domain\Seller\Actions\RequestStoreNameChangeAction;
use App\Domain\Seller\Actions\RequestStoreReopenAction;
use App\Domain\Seller\Actions\WithdrawStoreNameChangeAction;
use App\Domain\Seller\Actions\GetSellerProfileAction;
use App\Domain\Seller\Actions\UpdateSellerProfileAction;
use App\Domain\Seller\Actions\UploadBankProofAction;
use App\Domain\Seller\Actions\UploadSellerLogoAction;
use App\Domain\Seller\DTO\UpdateSellerProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateSellerProfileRequest;
use App\Http\Requests\Seller\CloseSellerStoreRequest;
use App\Http\Requests\Seller\RequestStoreNameChangeRequest;
use App\Http\Requests\Seller\UploadBankProofRequest;
use App\Http\Requests\Seller\UploadSellerLogoRequest;
use App\Http\Resources\Seller\SellerProfileResource;
use Illuminate\Http\Request;

/**
 * Approved seller manages their OWN business — resolved from
 * $request->user(), no {id} anywhere.
 */
final class SellerProfileController extends Controller
{
    // View my business profile (bank shown masked)
    public function show(
        Request $request,
        GetSellerProfileAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute($request->user()->id),
        );
    }

    // Update description / address / payout bank (JSON body)
    public function update(
        UpdateSellerProfileRequest $request,
        UpdateSellerProfileAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute(
                $request->user()->id,
                UpdateSellerProfileDTO::fromArray($request->validated()),
            ),
        );
    }

    // Upload or replace the store logo (multipart — separate from PUT,
    // because PHP does not parse multipart bodies on PUT requests)
    public function uploadLogo(
        UploadSellerLogoRequest $request,
        UploadSellerLogoAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute(
                $request->user()->id,
                $request->file('logo'),
            ),
        );
    }

    // P6-2 — upload a bank statement proving the payout account is yours.
    // If the AI can read a matching account it is verified instantly,
    // otherwise it waits for an admin.
    public function uploadBankProof(
        UploadBankProofRequest $request,
        UploadBankProofAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute(
                $request->user()->id,
                $request->file('file'),
            ),
        );
    }

    // P8-5 — the seller's own exit. Requires them to type the store name
    // back (C34); a suspended store is refused (C30).
    public function close(
        CloseSellerStoreRequest $request,
        CloseSellerStoreAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute(
                $request->user()->id,
                $request->confirmStoreName(),
                $request->reason(),
            ),
        );
    }

    // P9-4 — ask to be renamed. C45: the old name stays live until an
    // admin approves; nothing customer-facing moves while this is pending.
    public function requestNameChange(
        RequestStoreNameChangeRequest $request,
        RequestStoreNameChangeAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute($request->user()->id, $request->storeName()),
        );
    }

    public function withdrawNameChange(
        Request $request,
        WithdrawStoreNameChangeAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute($request->user()->id),
        );
    }

    // P8-6 — ask an admin to reopen a closed store.
    public function requestReopen(
        Request $request,
        RequestStoreReopenAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute($request->user()->id),
        );
    }

    // Remove the store logo
    public function deleteLogo(
        Request $request,
        DeleteSellerLogoAction $action,
    ): SellerProfileResource {

        return new SellerProfileResource(
            $action->execute($request->user()->id),
        );
    }
}
