<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userRepository;
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 15); // Set pagination per page
        $users = $this->userRepository->getAllUsers($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show(int $id)
    {
        $user = $this->userRepository->getUser($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }
        //$user =Auth::user();

        if (Auth::user()->hasRole('Super Admin') || Auth::id() == $user->id) {
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to view this user.'
        ], 403);
    }
    public function destroy(string $id)
    {
        $deleted = $this->userRepository->deleteUser($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}
