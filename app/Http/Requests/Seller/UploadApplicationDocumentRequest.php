<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Domain\Seller\Enums\SellerDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadApplicationDocumentRequest extends FormRequest
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
            'document_type' => ['required', Rule::enum(SellerDocumentType::class)],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png', // D6 — CNIC can be a scan (pdf) or a photo
                'max:10240',              // 10 MB, in kilobytes (D6)
            ],
        ];
    }
}
