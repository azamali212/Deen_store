<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerApplicationDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Deliberately NO file_path — private storage location never leaves
        // the server (D7). Admins download through their own endpoint.
        return [
            'document_type' => $this->document_type->value,
            'label' => $this->document_type->label(),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'uploaded_at' => $this->updated_at,
            // Admin only: what the AI read off this document (decrypted
            // here, never stored in plain text).
            'ai' => $this->when(
                $request->routeIs('admin.seller-applications.*'),
                fn (): array => [
                    'status' => $this->ai_status?->value,
                    'label' => $this->ai_status?->label(),
                    'checked_at' => $this->ai_checked_at,
                    'extracted' => $this->resource->aiFields(),
                    'concerns' => $this->resource->aiConcerns(),
                ],
            ),
            // Only on admin routes — an authenticated, admin-only endpoint,
            // never a public file URL (D7).
            'download_url' => $this->when(
                $request->routeIs('admin.seller-applications.*'),
                fn (): string => route('admin.seller-applications.documents.download', [
                    'application' => $this->seller_application_id,
                    'type' => $this->document_type->value,
                ]),
            ),
        ];
    }
}
