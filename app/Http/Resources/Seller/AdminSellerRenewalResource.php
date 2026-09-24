<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Models\SellerDocumentRenewal;
use Illuminate\Http\Request;

/**
 * The reviewer's view: everything the seller sees, plus what the AI read
 * and a link to the file itself. A separate class rather than a flag on
 * the seller resource — a flag has to be set on every item, which breaks
 * as soon as the list is paginated.
 *
 * @mixin SellerDocumentRenewal
 */
final class AdminSellerRenewalResource extends SellerDocumentRenewalResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'ai_fields' => $this->aiFields(),
            'ai_concerns' => $this->aiConcerns(),
            'store' => [
                'id' => $this->sellerProfile?->id,
                'store_name' => $this->sellerProfile?->store_name,
                'kyc_status' => $this->sellerProfile?->kycStatus()->value,
                // Tells the reviewer whether money is being held up.
                'payouts_on_hold' => $this->sellerProfile?->kycStatus()->blocksPayout() ?? false,
            ],
            // Still never the raw file_path — only a route that checks the
            // caller is an admin.
            'download_url' => route('admin.seller-renewals.download', ['renewal' => $this->id]),
        ];
    }
}
