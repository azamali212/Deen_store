<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password', // Check if confirm_password matches password
            'role' => 'nullable|string|in:Admin,Super Admin,Vendor Admin,Customer',
        ];
    }
    
    public function messages()
    {
        return [
            'email.unique' => 'The email has already been taken.',
            'confirm_password.same' => 'Password confirmation does not match.', // Correct message for confirm_password
        ];
    }
}
