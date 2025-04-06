<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $userRepository;
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function getAllUsers(Request $request)
    {
        $perPage = $request->get('perPage', 15); // Set pagination per page
        $users = $this->userRepository->getAllUsers($request);
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
    public function show($id, Request $request)
    {
        // Extract 'relations' from the query string if provided
        $relations = $request->input('relations', []);

        // Call the repository method to fetch the user by ID with the given relations
        $user = $this->userRepository->getUserById($id, $relations);

        // If user is not found, return 404
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Return the user data
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function createUser(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string|exists:roles,name', // Optional role
            'roles' => 'sometimes|array',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user = $this->userRepository->createUser($validatedData);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function updateUser($id, Request $request)
    {
        // Step 1: Validate the incoming data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'roles' => 'nullable|array',  // Ensure roles are an array if provided
            'roles.*' => 'exists:roles,name',  // Validate each role name exists in the roles table
            'permissions' => 'nullable|array',  // Ensure permissions are an array if provided
            'permissions.*' => 'exists:permissions,name',  // Validate each permission exists
            'profile_picture' => 'nullable|string',  // Assuming a file path or URL for the profile picture
        ]);

        // Step 2: Prepare data to pass to the repository method
        $data = $validated;

        // Step 3: Call the repository method to update the user
        try {
            $user = $this->userRepository->updateUser($id, $data);

            // Step 4: Return the updated user with roles and permissions
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            // Handle exception if any
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteUser($id)
    {
        $user = $this->userRepository->deleteUser($id);
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
            //"roles" => $user->roles,
            'data' => $user
        ]);
    }

    public function recycleBinUsers()
    {
        try {
            // Call the showRecycleBinUsers method from the repository
            $response = $this->userRepository->showRecycleBinUsers();

            // If the repository returns an error, it will already contain the proper status and message
            return $response;
        } catch (\Exception $e) {
            // Log the exception details
            Log::error('Error fetching recycle bin users', [
                'exception' => $e->getMessage(),
            ]);

            // Return a generic error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching recycle bin users.',
            ], 500);
        }
    }
    public function restoreUser($id)
    {
        try {
            // Call the restoreUser method from the repository
            $response = $this->userRepository->restoreUser($id);

            // If the repository returns an error, it will already contain the proper status and message
            return $response;
        } catch (\Exception $e) {
            // Log the exception details
            Log::error('Error restoring user', [
                'user_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Return a generic error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while restoring the user.',
            ], 500);
        }
    }
    public function forceDeleteUser($id)
    {
        $user = $this->userRepository->forceDeleteUser($id);
        return response()->json([
            'success' => true,
            'message' => 'User permanently deleted successfully',
            'data' => $user
        ]);
    }
}
