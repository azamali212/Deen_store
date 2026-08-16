<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Auth\DTO\VerifyTwoFactorDTO;
use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\LoginProvider;
use App\Domain\Auth\Services\DeviceFingerprintService;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ];
    }

    public function toDto(): VerifyTwoFactorDTO
    {
        return new VerifyTwoFactorDTO(
            identifier: $this->validated('identifier'),
            code: $this->validated('code'),

            panel: AuthPanel::ADMIN,
            provider: LoginProvider::PASSWORD,

            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),

            deviceName: app(DeviceFingerprintService::class)
                ->deviceName($this),
        );
    }
}
