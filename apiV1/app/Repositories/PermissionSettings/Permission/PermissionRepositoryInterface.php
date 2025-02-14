<?php 

namespace App\Repositories\PermissionSettings\Permission;

interface PermissionRepositoryInterface{
    public function getPermissions();
    public function getPermission($id);
    public function createPermission(array $data);
    public function updatePermission(array $data, $id);
    public function deletePermission($id);   

    public function getPermissionDetails($permissionId, $roleSlug = null, $userId = null, $email = null);
}