<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * P12-2 — erasure is re-authenticated and typed out.
 *
 * Two separate guards, because they stop two different mistakes.
 *
 * `password` stops somebody else doing it. A stolen access token is enough
 * to read the account; it must not be enough to destroy it. This is the same
 * reasoning behind the grace period, one layer earlier.
 *
 * `confirmation` stops the owner doing it by accident — a mis-tapped button
 * on a phone cannot type the phrase.
 *
 * Laravel's built-in `current_password:sanctum` rule is NOT used here. That
 * rule calls validate() on the named guard, and Sanctum's guard is a
 * RequestGuard whose validate() expects a 'request' key in the credentials —
 * so it throws rather than returning false. Checking the hash directly is
 * one line and works under any guard.
 */
final class RequestAccountErasureRequest extends FormRequest
{
    public const CONFIRMATION_PHRASE = 'DELETE MY ACCOUNT';

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
            'password' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $user = $this->user();

                    if ($user === null || ! Hash::check((string) $value, (string) $user->password)) {
                        $fail('That password is not correct.');
                    }
                },
            ],
            'confirmation' => [
                'required',
                'string',
                Rule::in([self::CONFIRMATION_PHRASE]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'Type "'.self::CONFIRMATION_PHRASE.'" exactly to confirm.',
        ];
    }
}
