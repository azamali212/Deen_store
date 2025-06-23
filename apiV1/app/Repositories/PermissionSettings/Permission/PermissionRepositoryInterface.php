<?php 

namespace App\Repositories\PermissionSettings\Permission;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel;

interface PermissionRepositoryInterface{
    public function getPermissions();
    public function getPermission($id);
    public function createPermission(array $data);
    public function updatePermission(array $data, $id);
    public function deletePermission($id);   
    public function getPermissionDetails($permissionId, $roleSlug = null, $userId = null, $email = null);
    public function getPermissionDistribution();
    public function exportPermissionsToExcel();

    public function importPermissions(UploadedFile $file,Excel $excel): array;
    public function bulkDelete(array $ids, bool $softDelete = false): array;
}