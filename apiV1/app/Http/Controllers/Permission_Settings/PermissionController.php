<?php

namespace App\Http\Controllers\Permission_Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\PermissionCreateRequest;
use App\Http\Requests\Permission\PermissionUpdateRequest;
use App\Repositories\PermissionSettings\Permission\PermissionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    protected $permissionRepository;

    public function __construct(PermissionRepositoryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Get all permissions with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $permissions = $this->permissionRepository->getPermissions($perPage);
        return response()->json(['data' => $permissions], 200);
    }
    public function show($id): JsonResponse
    {
        try {
            $permission = $this->permissionRepository->getPermission($id);
            return response()->json(['data' => $permission], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Permission not found'], 404);
        }
    }
    /**
     * Create a new permission
     */
    public function store(PermissionCreateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $permission = $this->permissionRepository->createPermission($data);
            return response()->json(['message' => 'Permission created successfully', 'data' => $permission], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        }
    }
    public function update(PermissionUpdateRequest $request, $id): JsonResponse
    {

        $validatedData = $request->validated();
        $permission = $this->permissionRepository->updatePermission($validatedData, $id);

        return response()->json([
            'message' => 'Permission updated successfully!',
            'permission' => $permission
        ]);
    }

    /**
     * Delete a permission
     */
    public function destroy($id): JsonResponse
    {
        try {
            $deleted = $this->permissionRepository->deletePermission($id);
            if ($deleted) {
                return response()->json(['message' => 'Permission deleted successfully'], 200);
            }
            return response()->json(['error' => 'Permission not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting permission'], 500);
        }
    }
    public function getPermissionDetails(Request $request, $permissionId): JsonResponse
    {
        try {
            $roleSlug = $request->query('role_slug');
            $userId = $request->query('user_id');
            $email = $request->query('email');
            $perPage = $request->query('per_page', 10);

            $details = $this->permissionRepository->getPermissionDetails($permissionId, $roleSlug, $userId, $email, $perPage);
            if (!$details) {
                return response()->json(['error' => 'Permission not found'], 404);
            }

            return response()->json(['data' => $details], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching permission details'], 500);
        }
    }
}
