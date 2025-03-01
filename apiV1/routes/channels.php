<?php
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Authorizing access to the user-specific channel (based on user ID)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // Ensure that the user can only join their own channel
    return (int) $user->id === (int) $id;
});

Broadcast::channel('email.status.up.{emailId}', function ($user, $emailId) {
    // Ensure the user is authenticated before proceeding
    if (!auth()->check()) {
        return false; // Deny access if not authenticated
    }

    // Ensure that the user is authorized to listen to updates for this email
    $email = Email::find($emailId);
    
    // Make sure the email exists and the user is the owner of the email
    return $email && $user->id === $email->user_id;
});

Broadcast::channel('email.{emailid}', function ($user, $emailid) {
    return $user->id === Email::findOrNew($emailid)->user_id;
});