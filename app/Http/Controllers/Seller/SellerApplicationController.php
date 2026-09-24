<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\CreateSellerApplicationAction;
use App\Domain\Seller\Actions\GetMySellerApplicationAction;
use App\Domain\Seller\Actions\SubmitSellerApplicationAction;
use App\Domain\Seller\Actions\UpdateSellerApplicationAction;
use App\Domain\Seller\Actions\UploadApplicationDocumentAction;
use App\Domain\Seller\DTO\CreateSellerApplicationDTO;
use App\Domain\Seller\DTO\UpdateSellerApplicationDTO;
use App\Domain\Seller\DTO\UploadApplicationDocumentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\CreateSellerApplicationRequest;
use App\Http\Requests\Seller\UpdateSellerApplicationRequest;
use App\Http\Requests\Seller\UploadApplicationDocumentRequest;
use App\Http\Resources\Seller\SellerApplicationDocumentResource;
use App\Http\Resources\Seller\SellerApplicationResource;
use Illuminate\Http\Request;

/**
 * Customer side of seller onboarding. No {id} anywhere — every method
 * resolves "my application" from $request->user() (BLUEPRINT C5).
 */
final class SellerApplicationController extends Controller
{
    // Start a business: creates the application as a draft
    public function store(
        CreateSellerApplicationRequest $request,
        CreateSellerApplicationAction $action,
    ): SellerApplicationResource {

        return new SellerApplicationResource(
            $action->execute(
                $request->user(),
                CreateSellerApplicationDTO::fromArray($request->validated()),
            )->load('documents'),
        );
    }

    // View my application: status, rejection reason, documents, what's missing
    public function show(
        Request $request,
        GetMySellerApplicationAction $action,
    ): SellerApplicationResource {

        return new SellerApplicationResource(
            $action->execute($request->user()->id),
        );
    }

    // Edit business info (draft or rejected only)
    public function update(
        UpdateSellerApplicationRequest $request,
        UpdateSellerApplicationAction $action,
    ): SellerApplicationResource {

        return new SellerApplicationResource(
            $action->execute(
                $request->user()->id,
                UpdateSellerApplicationDTO::fromArray($request->validated()),
            ),
        );
    }

    // Upload or replace one of the 5 documents (draft or rejected only)
    public function uploadDocument(
        UploadApplicationDocumentRequest $request,
        UploadApplicationDocumentAction $action,
    ): SellerApplicationDocumentResource {

        return new SellerApplicationDocumentResource(
            $action->execute(
                $request->user()->id,
                UploadApplicationDocumentDTO::fromArray($request->validated()),
            ),
        );
    }

    // Submit for review (first time) or resubmit after a rejection
    public function submit(
        Request $request,
        SubmitSellerApplicationAction $action,
    ): SellerApplicationResource {

        return new SellerApplicationResource(
            $action->execute($request->user()),
        );
    }
}
