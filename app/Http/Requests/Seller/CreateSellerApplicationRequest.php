<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Domain\Seller\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateSellerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Uniqueness is a domain rule (DuplicateStoreNameException in
            // SellerApplicationService), not checked here twice.
            'store_name' => ['required', 'string', 'min:3', 'max:100'],
            'business_name' => ['required', 'string', 'min:2', 'max:150'],
            'business_type' => ['required', Rule::enum(BusinessType::class)],
            // P10-1 — ISO 3166-1 alpha-2. A country we have no rules for
            // still works: CountryDocumentMap falls back to a passport
            // (P10-4), so we accept any well-formed code rather than
            // turning away a seller from somewhere we have not listed.
            'country' => ['required', 'string', 'size:2', 'alpha'],
            // Documents may be checked by an automated AI service
            // (BLUEPRINT section 10) — the applicant must agree up front.
            'accept_document_processing' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accept_document_processing.accepted' => 'Please agree that your documents may be checked by an automated verification service before an admin reviews them.',
        ];
    }
}
