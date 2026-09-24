<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\DownloadRenewalDocumentAction;
use App\Domain\Seller\Actions\ListPendingRenewalsAction;
use App\Domain\Seller\Actions\ReviewDocumentRenewalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ReviewDocumentRenewalRequest;
use App\Http\Resources\Seller\AdminSellerRenewalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminSellerRenewalController extends Controller
{
    public function index(Request $request, ListPendingRenewalsAction $action): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        return AdminSellerRenewalResource::collection($action->execute($perPage));
    }

    public function download(int $renewal, Request $request, DownloadRenewalDocumentAction $action): StreamedResponse
    {
        return $action->execute($renewal, (int) $request->user()->id);
    }

    public function review(int $renewal, ReviewDocumentRenewalRequest $request, ReviewDocumentRenewalAction $action): JsonResource
    {
        return AdminSellerRenewalResource::make(
            $action->execute(
                $renewal,
                (int) $request->user()->id,
                $request->approves(),
                $request->reason(),
            ),
        );
    }
}
