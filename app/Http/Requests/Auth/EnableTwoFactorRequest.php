<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Auth\DTO\EnableTwoFactorDTO;
use App\Domain\Auth\Enums\TwoFactorProvider;
use Illuminate\Foundation\Http\FormRequest;

final class EnableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => [
                'required',
                'string',
                'in:google',
            ],
        ];
    }

    public function toDto(): EnableTwoFactorDTO
    {
        return new EnableTwoFactorDTO(
            userId: (string) $this->user()->id,
            provider: TwoFactorProvider::from(
                $this->string('provider')->value(),
            ),
        );
    }
}
