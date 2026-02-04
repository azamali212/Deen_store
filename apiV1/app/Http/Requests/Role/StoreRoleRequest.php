<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Assuming only admins can create roles
        return auth()->user()->can('role-create');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'permission_names' => 'nullable|array',
            'permission_names.*' => 'string|exists:permissions,name', // Make sure this exists
            'description' => 'nullable|string|max:500',
        ];
    }

    // Add this method to properly prepare data
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Ensure permission_names is always an array
        $validated['permission_names'] = $validated['permission_names'] ?? [];
        
        return $validated;
    }
}
