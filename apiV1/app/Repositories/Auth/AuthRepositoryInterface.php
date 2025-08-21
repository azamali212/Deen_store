<?php 

namespace App\Repositories\Auth;

use App\Models\User;

interface AuthRepositoryInterface {

   
    public function register(array $data);
    public function login(array $credentials, $guard = null);
    public function getCurrentGuard($tokenName);
    public function verifyEmail($token);
    public function forgotPassword($email);
    public function resetPassword($token, $newPassword);
    public function logout();

    public function switchRole(User $user, string $role);
    public function getValidGuardsForRole($role);
    public function getDefaultGuardForRole($role);
    public function resendVerificationEmail($email);
}