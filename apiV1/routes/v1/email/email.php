<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EmailMarket\EmailController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    // Send an email (POST request)
    Route::post('send-email', [EmailController::class, 'sendEmail']);

    // Get emails for a specific user (GET request)
    Route::get('user-emails/{userId}/{status?}', [EmailController::class, 'getUserEmails']);

    // Mark an email as read (POST request)
    Route::post('mark-as-read/{emailId}', [EmailController::class, 'markAsRead']);

    // Mark an email as unread (POST request)
    Route::post('mark-as-unread/{emailId}', [EmailController::class, 'markAsUnread']);

    // Archive an email (POST request)
    Route::post('archive-email/{emailId}', [EmailController::class, 'archiveEmail']);

    // Unarchive an email (POST request)
    Route::post('unarchive-email/{emailId}', [EmailController::class, 'unarchiveEmail']);

    // Delete an email (DELETE request)
    Route::delete('delete-email/{emailId}', [EmailController::class, 'deleteEmail']);
});
