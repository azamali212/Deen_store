<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', function ($user, $userId): bool {
    return (string) $user->id === (string) $userId;
});