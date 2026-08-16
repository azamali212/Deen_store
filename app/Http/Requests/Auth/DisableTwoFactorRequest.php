<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Auth\DTO\DisableTwoFactorDTO;
use Illuminate\Foundation\Http\FormRequest;

final class DisableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
            ],
        ];
    }

    public function toDto(): DisableTwoFactorDTO
    {
        return new DisableTwoFactorDTO(
            userId: (string) $this->user()->id,
            password: $this->string('password')->value(),
        );
    }
}
