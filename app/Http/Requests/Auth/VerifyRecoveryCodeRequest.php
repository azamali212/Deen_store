<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Auth\DTO\VerifyRecoveryCodeDTO;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyRecoveryCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
            ],
        ];
    }

    public function toDto(): VerifyRecoveryCodeDTO
    {
        return new VerifyRecoveryCodeDTO(
            userId: (string) $this->user()->id,
            code: $this->string('code')->value(),
        );
    }
}
