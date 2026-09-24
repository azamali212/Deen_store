<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Enums\SellerRenewalStatus;
use App\Models\SellerApplicationDocument;
use App\Models\SellerDocumentRenewal;
use App\Models\SellerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phase 9a — we collected identity documents and never deleted any of them.
 *
 * Closure (13b) is what made this fixable: until a relationship could END,
 * there was no clock to start. A quiet seller is still a seller (C41).
 */
final readonly class SellerKycRetentionService
{
    public function __construct(
        private DocumentStorageInterface $storage,
    ) {}

    /**
     * @return array{superseded: int, closed: int, failed: int}
     */
    public function purge(): array
    {
        $failed = 0;

        $superseded = $this->purgeSupersededRenewals($failed);
        $closed = $this->purgeClosedStoreDocuments($failed);

        return ['superseded' => $superseded, 'closed' => $closed, 'failed' => $failed];
    }

    /**
     * A rejected renewal, or an approved one that a NEWER approved renewal
     * of the same type has replaced. Neither proves anything current.
     */
    private function purgeSupersededRenewals(int &$failed): int
    {
        $cutoff = now()->subDays((int) config('seller.superseded_retention_days'));
        $purged = 0;

        SellerDocumentRenewal::query()
            ->whereNull('file_purged_at')
            ->where('updated_at', '<', $cutoff)
            ->where(fn (Builder $q): Builder => $q
                ->where('status', SellerRenewalStatus::REJECTED->value)
                ->orWhere(fn (Builder $inner): Builder => $inner
                    ->where('status', SellerRenewalStatus::APPROVED->value)
                    ->whereExists(fn (QueryBuilder $sub): QueryBuilder => $sub
                        ->selectRaw('1')
                        ->from('seller_document_renewals as newer')
                        ->whereColumn('newer.seller_profile_id', 'seller_document_renewals.seller_profile_id')
                        ->whereColumn('newer.document_type', 'seller_document_renewals.document_type')
                        ->whereColumn('newer.id', '>', 'seller_document_renewals.id')
                        ->where('newer.status', SellerRenewalStatus::APPROVED->value))))
            // C42 — never touch a store that is under investigation.
            ->whereHas('sellerProfile', fn (Builder $q): Builder => $q
                ->where('status', '!=', SellerProfileStatus::SUSPENDED->value))
            ->orderBy('id')
            ->chunkById(200, function ($renewals) use (&$purged, &$failed): void {
                foreach ($renewals as $renewal) {
                    if ($this->purgeFile($renewal, $renewal->file_path, $failed)) {
                        $purged++;
                    }
                }
            });

        return $purged;
    }

    /**
     * Everything belonging to a store that CLOSED longer ago than the
     * retention window — the onboarding documents and every renewal.
     */
    private function purgeClosedStoreDocuments(int &$failed): int
    {
        $cutoff = now()->subDays((int) config('seller.kyc_retention_days'));
        $purged = 0;

        // C41 — closed_at drives this, never "inactive for a while".
        // C42 — a suspended store can never match, because a store is
        // either closed or suspended, not both.
        $stores = SellerProfile::query()
            ->where('status', SellerProfileStatus::CLOSED->value)
            ->whereNotNull('closed_at')
            ->where('closed_at', '<', $cutoff)
            ->get(['id', 'seller_application_id']);

        if ($stores->isEmpty()) {
            return 0;
        }

        $storeIds = $stores->pluck('id')->all();
        $applicationIds = $stores->pluck('seller_application_id')->filter()->all();

        SellerDocumentRenewal::query()
            ->whereNull('file_purged_at')
            ->whereIn('seller_profile_id', $storeIds)
            ->orderBy('id')
            ->chunkById(200, function ($renewals) use (&$purged, &$failed): void {
                foreach ($renewals as $renewal) {
                    if ($this->purgeFile($renewal, $renewal->file_path, $failed)) {
                        $purged++;
                    }
                }
            });

        if ($applicationIds !== []) {
            SellerApplicationDocument::query()
                ->whereNull('file_purged_at')
                ->whereIn('seller_application_id', $applicationIds)
                ->orderBy('id')
                ->chunkById(200, function ($documents) use (&$purged, &$failed): void {
                    foreach ($documents as $document) {
                        if ($this->purgeFile($document, $document->file_path, $failed)) {
                            $purged++;
                        }
                    }
                });
        }

        return $purged;
    }

    /**
     * C43 — file FIRST, then the stamp. If this dies in between, the next
     * run finds a file that is already gone and simply stamps the row. The
     * other order would leave a file on disk that nothing points at any
     * more — invisible, and therefore never cleaned up.
     */
    private function purgeFile(Model $row, ?string $path, int &$failed): bool
    {
        try {
            if ($path !== null && $path !== '') {
                $this->storage->delete($path);
            }

            $row->forceFill(['file_purged_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            // One unreadable file must not stop the whole sweep.
            $failed++;

            Log::error('[Seller][Retention] Could not purge a KYC document.', [
                'model' => $row::class,
                'id' => $row->getKey(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
