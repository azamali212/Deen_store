<?php
namespace App\Repositories\PermissionSettings\Role;

interface RoleRepositoryInterface
{
    public function getRoles();
    
    public function getRoleById($id);
    
    public function createRole($data);
    
    public function updateRole($data, $id);
    
    public function deleteRole($id);
    
    public function getRolePermissions($id);
    
    public function attachPermissions($data);
    
    public function detachPermissions(array $data);
    
    public function getRoleUsers($id);
    
    public function attachUsers($data);
    
    public function detachUsers($data);

    // Role retrieval by slug
    public function getRoleBySlug($slug);

    // Role and permissions by slug and user
    public function getRolePermissionsBySlugAndUser($slug, $user);
    public function getRoleBySlugAndUser($slug, $user);

    // Role and permissions by user ID
    public function getRoleBySlugAndUserId($slug, $userId);
    public function getRolePermissionsBySlugAndUserId($slug, $userId);
    
    public function getRoleBySlugAndUserEmail($slug, $email);
    public function getRolePermissionsBySlugAndUserEmail($slug, $email);
}