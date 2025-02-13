<?php 

namespace App\Repositories\Auth;

interface AuthRepositoryInterface {

   
    public function register(array $data);
    public function login(array $credentials);
    public function verifyEmail($token);
    public function forgotPassword($email);
    public function resetPassword($token, $newPassword);
    public function logout();
}