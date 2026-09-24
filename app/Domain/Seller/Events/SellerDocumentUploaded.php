<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerApplicationDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerDocumentUploaded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerApplicationDocument $document,
    ) {}
}
