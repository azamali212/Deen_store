<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Auth\DTO\ConfirmTwoFactorDTO;
use Illuminate\Foundation\Http\FormRequest;

final class ConfirmTwoFactorRequest extends FormRequest
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
                'digits:6',
            ],
        ];
    }

    public function toDto(): ConfirmTwoFactorDTO
    {
        return new ConfirmTwoFactorDTO(
            userId: (string) $this->user()->id,
            code: $this->string('code')->value(),
        );
    }
}
