<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\User\Enums\ConsentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GrantConsentRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                Rule::in(ConsentType::values()),
            ],

            // Deliberately NOT accepted from the client. The version recorded
            // is always the one currently in force on the server — otherwise
            // a client could claim the user agreed to wording they never saw.
        ];
    }

    public function consentType(): ConsentType
    {
        return ConsentType::from((string) $this->string('type'));
    }
}
