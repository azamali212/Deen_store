<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class TokenService
{
    public function generateTokens(User $user, string $sessionId): array
    {
        $accessToken = $user
            ->createToken("access_{$sessionId}")
            ->plainTextToken;

        $refreshToken = Str::random(64);

        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'token' => hash('sha256', $refreshToken),
            'created_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $hashed = hash('sha256', $refreshToken);

        $record = DB::table('refresh_tokens')
            ->where('token', $hashed)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            throw new Exception('Invalid refresh token');
        }

        $user = User::findOrFail($record->user_id);

        // rotate refresh token
        $newRefresh = Str::random(64);

        DB::table('refresh_tokens')
            ->where('id', $record->id)
            ->update([
                'token' => hash('sha256', $newRefresh),
                'updated_at' => now()
            ]);

        return [
            'access_token' => $user
                ->createToken('refresh_' . time())
                ->plainTextToken,
            'refresh_token' => $newRefresh,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];
    }
}