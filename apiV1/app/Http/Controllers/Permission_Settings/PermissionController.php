<?php

namespace App\Http\Controllers\Permission_Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\PermissionCreateRequest;
use App\Http\Requests\Permission\PermissionUpdateRequest;
use App\Repositories\PermissionSettings\Permission\PermissionRepositoryInterface;
use Maatwebsite\Excel\Excel;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class PermissionController extends Controller
{
    protected $excel;
    protected $permissionRepository;

    public function __construct(PermissionRepositoryInterface $permissionRepository, Excel $excel)
    {
        $this->permissionRepository = $permissionRepository;
        $this->excel = $excel;
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            return response()->json(['error' => 'Error deleting permission'], 500);
        }
    }

    /**
     * Delete multiple permissions
     */
    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:permissions,id',
            'soft_delete' => 'sometimes|boolean'
        ]);

        try {
            $result = $this->permissionRepository->bulkDelete(
                $validated['ids'],
                $validated['soft_delete'] ?? false
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'deleted_count' => $result['count'],
                    'failed_ids' => $result['failed_ids'],
                    'skipped_ids' => $result['skipped_ids']
                ],
                'metadata' => [
                    'total_requested' => count($validated['ids']),
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed',
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ]
            ], $this->getStatusCodeFromException($e));
        }
    }

    protected function getStatusCodeFromException(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        if ($e instanceof ModelNotFoundException) {
            return 404;
        }
        if ($e instanceof AuthorizationException) {
            return 403;
        }
        if ($e instanceof ValidationException) {
            return 422;
        }
        return 500;
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
        } catch (Exception $e) {
            return response()->json(['error' => 'Error fetching permission details'], 500);
        }
    }

    public function getPermissionDistribution(): JsonResponse
    {
        try {
            $distribution = $this->permissionRepository->getPermissionDistribution();
            return response()->json(['data' => $distribution], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error fetching permission distribution'], 500);
        }
    }

    public function exportPermissionsToExcel()
    {
        try {
            $filePath = $this->permissionRepository->exportPermissionsToExcel();

            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
                'Access-Control-Expose-Headers' => 'Content-Disposition'
            ];

            return response()->download($filePath, basename($filePath), $headers);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error fetching permission distribution'], 500);
        }
    }

    public function importPermissions(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $file = $request->file('file');
            $result = $this->permissionRepository->importPermissions($file, $this->excel);

            if ($result['status']) {
                return response()->json([
                    'status' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => $result['message'],
                'errors' => $result['errors']
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Import failed',
                'errors' => [$this->formatImportError($e->getMessage())]
            ], 500);
        }
    }

    private function formatImportError(string $message): string
    {
        // Clean up validation messages
        $message = preg_replace('/The \d+\./', 'The ', $message);
        return preg_replace('/row \d+:/', '', $message);
    }
}
