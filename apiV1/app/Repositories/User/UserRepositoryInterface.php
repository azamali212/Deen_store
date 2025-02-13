<?php

namespace App\Repositories\User;

interface UserRepositoryInterface
{
    public function getUser($id);
    public function getAllUsers();
    public function deleteUser(string $id);
    
}
