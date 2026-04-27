<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class LogoutDeactivatedUsers implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

     public function __construct()
    {
        //
    }
    protected $signature = 'users:logout-deactivated';
    protected $description = 'Log out any deactivated users with active sessions';

    public function handle()
    {
        $deactivatedUsers = User::where('status', 'inactive')
            ->whereHas('tokens')
            ->get();

        foreach ($deactivatedUsers as $user) {
            $user->tokens()->delete();
            Log::info("Logged out deactivated user: {$user->email}");
        }
    }
}
