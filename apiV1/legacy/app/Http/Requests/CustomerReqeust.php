<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerReqeust extends FormRequest
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
    public function rules(): array
    {
        $rules = [
            'username' => 'required|string|max:255|unique:customers,username,',
            'address' => 'required|string|max:500',
            'phone_number' => 'nullable|string|max:20',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'post_code' => 'nullable|string|max:10',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'status' => 'required|in:active,inactive,suspended',
            'preferred_language' => 'nullable|string|max:50',
            'newsletter_subscription' => 'boolean',
            'user_id' => 'required|exists:users,id',
            'store_manager_id' => 'required|exists:store_managers,id',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            // For update requests, ignore the current record in unique validation
            $rules['username'] = 'required|string|max:255|unique:customers,username,' . $this->route('customer');
        }

        return $rules;
    }
}
