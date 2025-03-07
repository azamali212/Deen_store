<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EmailMarket\EmailController;
use App\Http\Controllers\EmailMarket\EmailDraftsController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    // Send an email (POST request)
    Route::post('send-email', [EmailController::class, 'sendEmail']);

    // Get emails for a specific user (GET request)
    Route::get('user-emails/{userId}/{status?}', [EmailController::class, 'getEmailsForUser']);

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
    Route::post('/trash/{id}', [EmailController::class, 'moveToTrash']);
    Route::post('/restore/{id}', [EmailController::class, 'restoreEmail']);
    Route::delete('/empty/{id}', [EmailController::class, 'emptyTrash']);
    Route::get('/trash/{userId}', [EmailController::class, 'getTrashedEmails']);

    //Email Drafts
    Route::post('/email-drafts', [EmailDraftsController::class, 'store']);

    // Get all drafts for a specific user
    Route::get('/email-drafts/{userId}', [EmailDraftsController::class, 'index']);

    // Get a specific draft by its ID
    Route::get('/{userId}/drafts/{draftId}', [EmailDraftsController::class, 'show']);

    // Delete a draft
    Route::delete('/{userId}/drafts/{draftId}', [EmailDraftsController::class, 'destroy']);

    // Restore a draft from the trash
    Route::put('/{userId}/drafts/{draftId}/restore', [EmailDraftsController::class, 'restore']);

    // Permanently delete a draft
    Route::delete('/{userId}/drafts/{draftId}/permanently', [EmailDraftsController::class, 'emptyTrash']);

    // Move a draft to the trash
    Route::put('/{userId}/drafts/{draftId}/trash', [EmailDraftsController::class, 'moveToTrash']);

    // Update the status of multiple drafts
    Route::put('/email-drafts/status', [EmailDraftsController::class, 'updateStatus']);

    // Lock a draft for editing
    Route::put('/lock/{draftId}', [EmailDraftsController::class, 'lock']);

    // Track the history of a draft
    Route::get('/history/{draftId}', [EmailDraftsController::class, 'trackHistory']);
});
