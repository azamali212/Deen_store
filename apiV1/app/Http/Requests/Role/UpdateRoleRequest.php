<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Assuming only admins can update roles
        return auth()->user()->can('role-edit');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->route('role'),
            'slug' => 'required|string|max:255|unique:roles,slug,' . $this->route('role'),
        ];
    }
}
