<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Domain\Seller\Enums\SellerTeamRole;
use Illuminate\Foundation\Http\FormRequest;

final class ChangeTeamMemberRoleRequest extends FormRequest
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
            'role' => ['required', 'in:'.implode(',', SellerTeamRole::assignableValues())],
        ];
    }
}
