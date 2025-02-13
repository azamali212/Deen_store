<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::select('*')->paginate($perPage);
    }
    public function getUser($id)
    {
        return User::find($id);
    }
    public function deleteUser(string $id): bool
    {
        $user = User::find($id);
        if ($user) {
            return $user->delete();
        }
        return false;
    }
}