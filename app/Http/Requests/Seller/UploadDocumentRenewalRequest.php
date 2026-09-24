<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Domain\Seller\Enums\SellerDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadDocumentRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware + SellerProfileService::guardKycDocuments().
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => [
                'required',
                // Only the documents that actually expire (P8-1). The tax
                // certificate never does, and the bank statement has its
                // own proof flow (P6-2).
                Rule::in(array_column(SellerDocumentType::renewable(), 'value')),
            ],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.in' => 'That document cannot be renewed. You can replace your CNIC or your business licence.',
        ];
    }

    public function documentType(): SellerDocumentType
    {
        return SellerDocumentType::from((string) $this->string('document_type'));
    }
}
