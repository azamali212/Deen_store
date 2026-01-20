<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class MultiGuardAuthenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        // If no guards passed → load all guards from auth.php automatically
        if (empty($guards)) {
            $guards = array_keys(config('auth.guards'));
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Set current guard so controllers can detect it
                Auth::shouldUse($guard);
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Unauthenticated',
            'guards_checked' => $guards
        ], 401);
    }
}
