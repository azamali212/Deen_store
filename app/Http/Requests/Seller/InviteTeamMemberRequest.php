<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Domain\Seller\Enums\SellerTeamRole;
use Illuminate\Foundation\Http\FormRequest;

final class InviteTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Whether the address has an account is a domain rule
            // (TeamInvitationNotAllowedException), not a DB lookup here.
            'email' => ['required', 'email', 'max:255'],
            // 'owner' is deliberately not accepted — a store has one owner.
            'role' => ['required', 'in:'.implode(',', SellerTeamRole::assignableValues())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Choose either manager or staff. A store can only have one owner.',
        ];
    }
}
