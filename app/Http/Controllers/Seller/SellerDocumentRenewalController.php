<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\ListDocumentRenewalsAction;
use App\Domain\Seller\Actions\UploadDocumentRenewalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UploadDocumentRenewalRequest;
use App\Http\Resources\Seller\SellerDocumentRenewalResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Phase 8a — the seller side of re-KYC.
 */
final class SellerDocumentRenewalController extends Controller
{
    public function index(Request $request, ListDocumentRenewalsAction $action): AnonymousResourceCollection
    {
        return SellerDocumentRenewalResource::collection(
            $action->execute((int) $request->user()->id),
        );
    }

    public function store(UploadDocumentRenewalRequest $request, UploadDocumentRenewalAction $action): JsonResponse
    {
        $renewal = $action->execute(
            (int) $request->user()->id,
            $request->documentType(),
            $request->file('file'),
        );

        return SellerDocumentRenewalResource::make($renewal)
            ->response()
            ->setStatusCode(201);
    }
}
