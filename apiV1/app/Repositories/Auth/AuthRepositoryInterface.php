<?php 

namespace App\Repositories\Auth;

use App\Models\User;

interface AuthRepositoryInterface {

   
    
    public function register(array $data);
    public function login(array $credentials, ?string $portal = null, ?array $location = null);
    public function refreshToken(string $refreshToken);
    public function logoutFromDevice(string $sessionId);
    public function getCurrentGuard($tokenName);
    public function verifyEmail($token);
    public function forgotPassword($email);
    public function resetPassword($token, $newPassword);
    public function logout();

    public function switchRole(User $user, string $role);
    public function getValidGuardsForRole($role);
    public function getDefaultGuardForRole($role);
    public function resendVerificationEmail($email);
    public function verifyOtp(string $email, string $sessionId, string $otp);
    public function getCurrentUser();
}