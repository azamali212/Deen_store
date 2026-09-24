<?php

declare(strict_types=1);

namespace App\Domain\Seller\Support;

use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\Exceptions\SellerDocumentNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class LocalDocumentStorage implements DocumentStorageInterface
{
    // The PRIVATE disk (storage/app/private) — never 'public' (D7).
    private const DISK = 'local';

    private const DIRECTORY = 'seller-documents';

    private const BANK_PROOF_DIRECTORY = 'seller-bank-proofs';

    public function store(int $applicationId, UploadedFile $file): string
    {
        // store() generates a random file name, so a user-supplied name
        // never becomes part of a path on our disk.
        return $file->store(self::DIRECTORY.'/'.$applicationId, self::DISK);
    }

    public function storeBankProof(int $sellerProfileId, UploadedFile $file): string
    {
        return $file->store(self::BANK_PROOF_DIRECTORY.'/'.$sellerProfileId, self::DISK);
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }

    public function download(string $path, string $downloadName): StreamedResponse
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            throw SellerDocumentNotFoundException::fileMissing();
        }

        return Storage::disk(self::DISK)->download($path, $downloadName);
    }
}
