<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Spatie\Permission\Models\Permission;

class PermissionExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Permission::with('roles')->get()->map(function ($permission) {
            return [
                'Name'       => $permission->name,
                'Slug'       => $permission->slug,
                'Roles'      => $permission->roles->pluck('name')->implode(', '),
                'Created At' => $permission->created_at->format('Y-m-d H:i:s'),
                'Updated At' => $permission->updated_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'Slug', 'Roles', 'Created At', 'Updated At'];
    }
}
