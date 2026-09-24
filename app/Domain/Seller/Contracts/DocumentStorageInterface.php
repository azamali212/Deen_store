<?php

declare(strict_types=1);

namespace App\Domain\Seller\Contracts;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Where seller KYC documents physically live. Same swappable idea as
 * AvatarStorageInterface — LocalDocumentStorage today, S3 later — with one
 * deliberate difference: there is NO url() method. These files are private
 * (CNIC, bank statements); the only way out is download(), which is only
 * ever called from an admin-only endpoint (BLUEPRINT D7).
 */
interface DocumentStorageInterface
{
    public function store(int $applicationId, UploadedFile $file): string;

    public function storeBankProof(int $sellerProfileId, UploadedFile $file): string;

    /** Phase 8a — a replacement KYC document from a live seller. */
    public function storeRenewal(int $sellerProfileId, UploadedFile $file): string;

    public function delete(string $path): void;

    public function download(string $path, string $downloadName): StreamedResponse;
}
