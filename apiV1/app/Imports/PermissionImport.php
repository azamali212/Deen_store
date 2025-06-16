<?php

namespace App\Imports;

use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Str;
use Throwable;

class PermissionImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError
{
    use SkipsErrors;

    private $rowCount = 0;
    private $skippedCount = 0;

    public function model(array $row)
{
    $name = $row['name'] ?? $row['permission'] ?? $row['permission_name'] ?? null;
    
    if (!$name) {
        $this->skippedCount++;
        return null;
    }

    // Check if permission already exists
    if (Permission::where('name', $name)->exists()) {
        $this->skippedCount++;
        return null;
    }

    $this->rowCount++;
    
    return Permission::firstOrCreate([
        'name' => $name
    ], [
        'guard_name' => $row['guard_name'] ?? 'web',
        'slug' => Str::slug($name) // Generate slug from name
    ]);
}

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'guard_name' => 'sometimes|string|max:255'
        ];
    }

    public function prepareForValidation($data)
    {
        // Map alternative column names to 'name'
        if (isset($data['permission'])) {
            $data['name'] = $data['permission'];
        }
        if (isset($data['permission_name'])) {
            $data['name'] = $data['permission_name'];
        }

        return $data;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}