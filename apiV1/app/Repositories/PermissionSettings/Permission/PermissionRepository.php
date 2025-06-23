<?php

namespace App\Repositories\PermissionSettings\Permission;


use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Exports\PermissionExport;
use App\Imports\PermissionImport;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PermissionRepository implements PermissionRepositoryInterface
{
    protected $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }
    public function getPermissions($perPage = 10)
    {
        return Permission::with('roles') // Eager load roles to reduce queries
            ->paginate($perPage);
    }

    public function getPermission($id)
    {
        return Permission::with(['roles', 'users'])->findOrFail($id);
    }

    public function createPermission(array $data)
    {
        return Permission::create($data);
    }

    public function updatePermission(array $data, $id)
    {
        $permission = Permission::findOrFail($id);
        $permission->update($data);
        $permission->save();
        return $permission;
    }

    public function deletePermission($id)
    {
        return Permission::destroy($id); // Uses direct deletion for better performance
    }

    public function bulkDelete(array $ids, bool $softDelete = false): array
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('No IDs provided for deletion.');
        }
    
        // Clean and validate IDs
        $ids = array_unique(array_filter(array_map('intval', $ids)));
        $existingIds = Permission::whereIn('id', $ids)->pluck('id')->toArray();
        
        $nonExistingIds = array_diff($ids, $existingIds);
        $deletableIds = array_intersect($ids, $existingIds);
        
        // Use transaction for atomic operations
        DB::beginTransaction();
        
        try {
            $deletedCount = 0;
            $failedIds = [];
            
            if ($softDelete && method_exists(Permission::class, 'delete')) {
                // Batch soft delete
                $deletedCount = Permission::whereIn('id', $deletableIds)->delete();
            } else {
                // Batch force delete
                $deletedCount = Permission::whereIn('id', $deletableIds)->forceDelete();
            }
            
            DB::commit();
            
            return [
                'count' => $deletedCount,
                'failed_ids' => [],
                'skipped_ids' => $nonExistingIds,
                'message' => $this->generateResultMessage($deletedCount, [], $nonExistingIds)
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            // Fallback to individual deletions if batch fails
            return $this->individualDeletionFallback($deletableIds, $nonExistingIds, $softDelete);
        }
    }
    
    protected function individualDeletionFallback(array $deletableIds, array $nonExistingIds, bool $softDelete): array
    {
        $deletedCount = 0;
        $failedIds = [];
        
        foreach ($deletableIds as $id) {
            try {
                $permission = Permission::withTrashed()->find($id);
                
                if ($softDelete && method_exists($permission, 'delete')) {
                    $permission->delete();
                } else {
                    $permission->forceDelete();
                }
                
                $deletedCount++;
            } catch (Exception $e) {
                $failedIds[$id] = $e->getMessage();
            }
        }
        
        return [
            'count' => $deletedCount,
            'failed_ids' => $failedIds,
            'skipped_ids' => $nonExistingIds,
            'message' => $this->generateResultMessage($deletedCount, $failedIds, $nonExistingIds)
        ];
    }
    
    protected function generateResultMessage(int $count, array $failedIds, array $skippedIds): string
    {
        $parts = [];
        
        if ($count > 0) {
            $parts[] = "Deleted {$count} permissions.";
        }
        
        if (!empty($failedIds)) {
            $parts[] = count($failedIds)." failed.";
        }
        
        if (!empty($skippedIds)) {
            $parts[] = count($skippedIds)." not found.";
        }
        
        return implode(' ', $parts) ?: 'No actions performed.';
    }
    public function getPermissionDetails($permissionId, $roleSlug = null, $userId = null, $email = null, $perPage = 10)
    {
        $permission = Permission::with(['roles', 'users'])->find($permissionId);
        if (!$permission) {
            return null;
        }

        // Get roles that have this permission (Paginated)
        $rolesWithPermission = Role::whereHas('permissions', function ($query) use ($permissionId) {
            $query->where('id', $permissionId);
        })->paginate($perPage);

        // Get users with this permission (Paginated)
        $usersWithPermission = User::whereHas('roles.permissions', function ($query) use ($permissionId) {
            $query->where('id', $permissionId);
        })->paginate($perPage);

        // Get permissions for a specific role if provided
        $rolePermissions = $roleSlug
            ? Role::where('slug', $roleSlug)->with('permissions')->first()
            : null;

        // Get user by ID or email (if provided)
        $user = null;
        if ($userId) {
            $user = User::with(['roles.permissions'])->find($userId);
        } elseif ($email) {
            $user = User::where('email', $email)->with(['roles.permissions'])->first();
        }

        return [
            'permission' => $permission,
            'roles_with_permission' => $rolesWithPermission,
            'users_with_permission' => $usersWithPermission,
            'role_permissions' => $rolePermissions,
            'user' => $user,
        ];
    }
    public function getPermissionDistribution()
    {
        // Get all permissions with their role and user counts
        $permissions = Permission::withCount(['roles', 'users'])->get();

        // Get total counts for normalization
        $totalRoles = Role::count();
        $totalUsers = User::count();

        // Prepare data for chart
        $distributionData = [
            'permissions' => [],
            'role_coverage' => 0,
            'user_coverage' => 0
        ];

        $totalRoleAssignments = 0;
        $totalUserAssignments = 0;

        foreach ($permissions as $permission) {
            $distributionData['permissions'][] = [
                'name' => $permission->name,
                'role_count' => $permission->roles_count,
                'user_count' => $permission->users_count,
                'role_percentage' => $totalRoles > 0 ? round(($permission->roles_count / $totalRoles) * 100, 2) : 0,
                'user_percentage' => $totalUsers > 0 ? round(($permission->users_count / $totalUsers) * 100, 2) : 0,
            ];

            $totalRoleAssignments += $permission->roles_count;
            $totalUserAssignments += $permission->users_count;
        }

        // Calculate overall coverage percentages
        if ($totalRoles > 0) {
            $distributionData['role_coverage'] = round(($totalRoleAssignments / ($totalRoles * count($permissions))) * 100, 2);
        }

        if ($totalUsers > 0) {
            $distributionData['user_coverage'] = round(($totalUserAssignments / ($totalUsers * count($permissions))) * 100, 2);
        }

        return $distributionData;
    }

    public function exportPermissionsToExcel(): string
    {
        $fileName = 'exports/permissions_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $this->excel->store(new PermissionExport(), $fileName, 'public');
        return storage_path('app/public/' . $fileName);
    }

    public function importPermissions(UploadedFile $file, Excel $excel): array
    {
        try {
            $import = new PermissionImport();
            $excel->import($import, $file);

            $importedCount = $import->getRowCount();
            $skippedCount = $import->getSkippedCount();

            return [
                'status' => true,
                'message' => "Successfully imported {$importedCount} permissions. {$skippedCount} skipped.",
                'data' => [
                    'imported' => $importedCount,
                    'skipped' => $skippedCount
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Import failed',
                'errors' => [$e->getMessage()]
            ];
        }
    }
}
