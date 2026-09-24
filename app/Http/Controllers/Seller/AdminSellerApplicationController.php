<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\ApproveSellerApplicationAction;
use App\Domain\Seller\Actions\DownloadApplicationDocumentAction;
use App\Domain\Seller\Actions\GetSellerApplicationDetailAction;
use App\Domain\Seller\Actions\ListSellerApplicationsAction;
use App\Domain\Seller\Actions\RejectSellerApplicationAction;
use App\Domain\Seller\DTO\ReviewSellerApplicationDTO;
use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ReviewSellerApplicationRequest;
use App\Http\Resources\Seller\SellerApplicationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin side of seller onboarding. The whole route group is behind
 * role:super_admin|platform_admin (D10).
 */
final class AdminSellerApplicationController extends Controller
{
    // List applications — ?status=pending (default) | approved | rejected | all
    public function index(
        Request $request,
        ListSellerApplicationsAction $action,
    ): AnonymousResourceCollection {

        $validated = $request->validate([
            // 'draft' is deliberately not accepted — drafts are never listed (C1).
            'status' => ['nullable', 'in:pending,approved,rejected,all'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            // Layer 3 — e.g. ?risk=high to review the riskiest first.
            'risk' => ['nullable', 'in:'.implode(',', AiRiskLevel::values())],
        ]);

        $statusParam = $validated['status'] ?? 'pending';

        return SellerApplicationResource::collection(
            $action->execute(
                $statusParam === 'all' ? null : SellerApplicationStatus::from($statusParam),
                (int) ($validated['per_page'] ?? 20),
                isset($validated['risk']) ? AiRiskLevel::from($validated['risk']) : null,
            ),
        );
    }

    // Full detail: business info, applicant, reviewer, all documents (+ download links)
    public function show(
        int $application,
        GetSellerApplicationDetailAction $action,
    ): SellerApplicationResource {

        return new SellerApplicationResource(
            $action->execute($application),
        );
    }

    // Stream one private document (CNIC, license, ...) to the admin
    public function downloadDocument(
        int $application,
        SellerDocumentType $type,
        DownloadApplicationDocumentAction $action,
    ): StreamedResponse {

        return $action->execute($application, $type);
    }

    // Approve or reject a pending application
    public function review(
        ReviewSellerApplicationRequest $request,
        int $application,
        ApproveSellerApplicationAction $approve,
        RejectSellerApplicationAction $reject,
    ): SellerApplicationResource {

        $dto = ReviewSellerApplicationDTO::fromArray(
            $request->validated(),
            $request->user()->id,
        );

        return new SellerApplicationResource(
            $dto->isApproval()
                ? $approve->execute($application, $dto)
                : $reject->execute($application, $dto),
        );
    }
}
