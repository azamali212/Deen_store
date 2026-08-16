<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Auth\DTO\RegenerateRecoveryCodesDTO;
use Illuminate\Foundation\Http\FormRequest;

final class RegenerateRecoveryCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toDto(): RegenerateRecoveryCodesDTO
    {
        return new RegenerateRecoveryCodesDTO(
            userId: (string) $this->user()->id,
        );
    }
}
