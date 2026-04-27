<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CartSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get Real IP Address
        $realIp = $request->header('X-Forwarded-For') ?? $request->ip();
        //Log::info('Detected IP:', ['ip' => $realIp]);

        // Rate Limiting (5 requests per minute)
        $key = 'cart-actions:' . $realIp;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many attempts. Try again later.'], 429);
        }
        RateLimiter::hit($key, 60);

        // Restrict IP Access (Example: Only allow trusted IPs)
        $allowedIps = ['192.168.1.1', '203.0.113.0', '31.94.32.78','127.0.0.1']; // Add your trusted IPs here
        if (!in_array($realIp, $allowedIps)) {
            return response()->json(['message' => 'Unauthorized IP address'], 403);
        }

        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        //shift  + option + A

       /*  // Double Authorization for Critical Actions
        if ($request->isMethod('delete') || $request->is('cart/clear*') || $request->is('cart/apply-discount')) {
            if (!$request->has('otp') || $request->input('otp') !== session('cart_otp')) {
                return response()->json(['message' => 'OTP verification required'], 403);
            }
        }

        // Validate Request Signature
        if (!$request->has('signature') || !Hash::check(env('SECURE_CART_KEY'), $request->input('signature'))) {
            return response()->json(['message' => 'Invalid request signature'], 403);
        } */

        return $next($request);
    }
}