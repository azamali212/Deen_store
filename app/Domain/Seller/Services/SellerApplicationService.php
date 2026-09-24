<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Permissions\Enums\SystemRole;
use App\Domain\Seller\DTO\CreateSellerApplicationDTO;
use App\Domain\Seller\DTO\ReviewSellerApplicationDTO;
use App\Domain\Seller\DTO\UpdateSellerApplicationDTO;
use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Events\SellerApplicationBlockedByAi;
use App\Domain\Seller\Exceptions\ApplicationFailedAiChecksException;
use App\Domain\Seller\Exceptions\CannotReviewOwnApplicationException;
use App\Domain\Seller\Exceptions\DuplicateStoreNameException;
use App\Domain\Seller\Exceptions\EmailNotVerifiedForSellingException;
use App\Domain\Seller\Exceptions\InvalidApplicationStatusTransitionException;
use App\Domain\Seller\Exceptions\SellerApplicationAlreadyExistsException;
use App\Domain\Seller\Exceptions\SellerApplicationNotFoundException;
use App\Domain\Seller\Repositories\Contracts\SellerApplicationRepositoryInterface;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Domain\Seller\Repositories\Contracts\SellerTeamRepositoryInterface;
use App\Models\SellerApplication;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class SellerApplicationService
{
    public function __construct(
        private SellerApplicationRepositoryInterface $applications,
        private SellerDocumentService $documents,
        private SellerProfileRepositoryInterface $profiles,
        private SellerDocumentVerificationService $verification,
        private SellerTeamRepositoryInterface $team,
        private SellerKycService $kyc,
    ) {}

    /**
     * Customer side: the ONLY way to get an application is through the
     * logged-in user's id — there is no {id} to tamper with (C5).
     */
    public function getForUser(int $userId): SellerApplication
    {
        return $this->applications->findForUser($userId)
            ?? throw SellerApplicationNotFoundException::forUser($userId);
    }

    public function create(User $user, CreateSellerApplicationDTO $dto): SellerApplication
    {
        $this->ensureEmailVerified($user);

        if ($this->applications->findForUser($user->id) !== null) {
            throw SellerApplicationAlreadyExistsException::forUser($user->id);
        }

        // C36 — P7-1 ("one person, one store") was only checked when
        // someone was INVITED. A staff member could still fill in a whole
        // application, pay for the AI checks, and only hit the wall at
        // approval. The answer is knowable right here, so it is answered
        // right here.
        if ($this->team->liveForUser((int) $user->id) !== null) {
            throw SellerApplicationAlreadyExistsException::alreadyInAStore((int) $user->id);
        }

        $this->ensureStoreNameAvailable($dto->storeName);

        return $this->applications->create($user->id, $dto);
    }

    public function update(int $userId, UpdateSellerApplicationDTO $dto): SellerApplication
    {
        $application = $this->getForUser($userId);

        if (! $application->status->isEditable()) {
            throw InvalidApplicationStatusTransitionException::cannot('edit', $application->status);
        }

        if ($dto->storeName !== null) {
            $this->ensureStoreNameAvailable($dto->storeName, exceptApplicationId: $application->id);
        }

        return $this->applications->update($application, $dto->toAttributes());
    }

    /**
     * draft -> pending (first submit) or rejected -> pending (resubmit).
     */
    public function submit(User $user, SellerApplication $application): SellerApplication
    {
        $this->ensureEmailVerified($user);

        if (! $application->status->isSubmittable()) {
            throw InvalidApplicationStatusTransitionException::cannot('submit', $application->status);
        }

        $this->documents->ensureAllRequiredPresent($application);

        // Layer 2 — compare what the AI read across all 5 documents. Hard
        // failures (P5-1) block the submit; warnings travel to the admin.
        $report = $this->verification->crossCheck($application);

        if ($report->failures() !== []) {
            event(new SellerApplicationBlockedByAi($application, $report->failures()));

            throw ApplicationFailedAiChecksException::withFailures($application->id, $report->failures());
        }

        return $this->applications->update($application, [
            'status' => SellerApplicationStatus::PENDING->value,
            'submitted_at' => now(),
            'ai_risk_level' => $report->riskLevel()->value,
            'ai_report' => $report->toArray(),
            'ai_checked_at' => now(),
            // A resubmission starts a fresh review: the old reason and the
            // old reviewer are cleared (the audit log keeps the history).
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    // ------------------------------------------------------------------
    // Admin side
    // ------------------------------------------------------------------

    public function listForAdmin(?SellerApplicationStatus $status, int $perPage, ?AiRiskLevel $risk = null): LengthAwarePaginator
    {
        return $this->applications->paginateForAdmin($status, $perPage, $risk);
    }

    public function getForAdmin(int $applicationId): SellerApplication
    {
        $application = $this->applications->findById($applicationId)
            ?? throw SellerApplicationNotFoundException::withId($applicationId);

        return $application->load(['user:id,name,email', 'reviewer:id,name,email', 'documents']);
    }

    /**
     * ONE transaction (C3): status change + seller_profiles row + 'seller'
     * role either ALL happen or NONE do — "approved but no seller role"
     * can't exist. assignRole() ADDS the role, so 'customer' is kept (C4).
     *
     * @return array{0: SellerApplication, 1: SellerProfile}
     */
    public function approve(int $applicationId, ReviewSellerApplicationDTO $dto): array
    {
        return DB::transaction(function () use ($applicationId, $dto): array {
            $application = $this->lockReviewable($applicationId, $dto->reviewerId, 'approve');

            $application = $this->applications->update($application, [
                'status' => SellerApplicationStatus::APPROVED->value,
                'rejection_reason' => null,
                'reviewed_by' => $dto->reviewerId,
                'reviewed_at' => now(),
            ]);

            $profile = $this->profiles->createFromApplication($application);

            // C22 — the owner is a team member too, so "which store is this
            // user in" has exactly one code path from the very first day.
            $this->team->createOwner((int) $profile->id, (int) $application->user_id);

            // P8-1 — lift the expiry DATES out of the encrypted findings
            // into plain, indexed columns. This is the only way a scheduled
            // job can ever find an expiring seller: encrypted values cannot
            // be queried. The identity numbers stay encrypted where they are.
            $expiry = $this->kycExpiryFrom($application);

            if ($expiry !== []) {
                $profile = $this->profiles->update($profile, $expiry);
                // A licence that already expires next month should say so
                // from day one, not wait for the first nightly run.
                $profile = $this->kyc->refresh($profile)['profile'];
            }

            $application->user->assignRole(SystemRole::SELLER->value);

            return [$application, $profile];
        });
    }

    /**
     * @return array<string, string> profile column => YYYY-MM-DD
     */
    private function kycExpiryFrom(SellerApplication $application): array
    {
        $attributes = [];

        foreach ($application->documents as $document) {
            $column = $document->document_type->expiryColumn();
            $field = $document->document_type->expiryField();

            if ($column === null || $field === null) {
                continue;
            }

            $value = $document->aiFields()[$field] ?? null;

            // AI may be off, or may not have read a date at all — then the
            // column stays NULL, which means "nothing known", NOT expired
            // (C27).
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                $attributes[$column] = $value;
            }
        }

        return $attributes;
    }

    public function reject(int $applicationId, ReviewSellerApplicationDTO $dto): SellerApplication
    {
        return DB::transaction(function () use ($applicationId, $dto): SellerApplication {
            $application = $this->lockReviewable($applicationId, $dto->reviewerId, 'reject');

            return $this->applications->update($application, [
                'status' => SellerApplicationStatus::REJECTED->value,
                'rejection_reason' => $dto->reason,
                'reviewed_by' => $dto->reviewerId,
                'reviewed_at' => now(),
            ]);
        });
    }

    /**
     * Row is locked FOR UPDATE until the transaction commits, so if two
     * admins click at the same moment the second one waits, then sees the
     * status already changed and gets a 409 instead of a double review.
     */
    private function lockReviewable(int $applicationId, int $reviewerId, string $action): SellerApplication
    {
        $application = $this->applications->lockForReview($applicationId)
            ?? throw SellerApplicationNotFoundException::withId($applicationId);

        if ((int) $application->user_id === $reviewerId) {
            throw CannotReviewOwnApplicationException::forReviewer($reviewerId, $applicationId);
        }

        if (! $application->status->isReviewable()) {
            throw InvalidApplicationStatusTransitionException::cannot($action, $application->status);
        }

        return $application;
    }

    private function ensureEmailVerified(User $user): void
    {
        if ($user->email_verified_at === null) {
            throw EmailNotVerifiedForSellingException::forUser($user->id);
        }
    }

    private function ensureStoreNameAvailable(string $storeName, ?int $exceptApplicationId = null): void
    {
        if ($this->applications->storeNameExists($storeName, $exceptApplicationId)) {
            throw DuplicateStoreNameException::taken($storeName);
        }
    }
}