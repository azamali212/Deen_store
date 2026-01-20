<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class AuditService
{
    public function log(
        string $event,
        ?string $userId = null,
        ?string $email = null,
        ?string $sessionId = null,
        array $meta = []
    ): void {
        $agent = new Agent();

        DB::table('login__audits')->insert([
            'user_id'    => $userId,
            'email'      => $email,
            'event'      => $event,
            'session_id' => $sessionId,

            'ip'         => request()->ip(),
            'device'     => $agent->device(),
            'browser'    => $agent->browser(),

            'meta'       => json_encode($meta),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
