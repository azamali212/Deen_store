<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class LogoutDeactivatedUsers extends Command
{
    protected $signature = 'users:logout-deactivated';
    protected $description = 'Log out any deactivated users with active sessions';

    public function handle()
    {
        $deactivatedUsers = User::where('status', 'inactive')
            ->whereHas('tokens')
            ->get();

        foreach ($deactivatedUsers as $user) {
            $user->tokens()->delete();
            $this->info("Logged out deactivated user: {$user->email}");
        }

        return 0;
    }
}