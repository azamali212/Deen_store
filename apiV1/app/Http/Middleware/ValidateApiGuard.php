<?php

namespace App\Http\Middleware;

use App\Repositories\Auth\AuthRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
    
        // Get token guard from token name
        $token = $user->currentAccessToken();
        $tokenParts = explode('_', $token->name);
        $currentGuard = $tokenParts[0] ?? null;
    
        // Validate guard against route requirements
        if (!empty($guards) && !in_array($currentGuard, $guards)) {
            $user->tokens()->delete(); // Force logout if guard mismatch
            return response()->json(['message' => 'Unauthorized guard'], 403);
        }
    
        return $next($request);
    }
}
