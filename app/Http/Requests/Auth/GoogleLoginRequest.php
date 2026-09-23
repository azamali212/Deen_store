<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The ID token Google's own "Sign in with Google" SDK hands
            // the frontend after the user picks their Google account —
            // this is what actually proves identity, so it's the only
            // thing we require here.
            'id_token' => ['required', 'string'],
        ];
    }
}
