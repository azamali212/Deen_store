<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EmailMarket\EmailController;
use App\Http\Controllers\EmailMarket\EmailDraftsController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::post('send-email', [EmailController::class, 'sendEmail'])
        ->middleware('permission:send-email');

    Route::get('user-emails/{userId}/{status?}', [EmailController::class, 'getEmailsForUser'])
        ->middleware('permission:view emails');

    Route::post('mark-as-read/{emailId}', [EmailController::class, 'markAsRead'])
        ->middleware('permission:mark email as read');

    Route::post('mark-as-unread/{emailId}', [EmailController::class, 'markAsUnread'])
        ->middleware('permission:mark email as unread');

    Route::post('archive-email/{emailId}', [EmailController::class, 'archiveEmail'])
        ->middleware('permission:archive email');

    Route::post('unarchive-email/{emailId}', [EmailController::class, 'unarchiveEmail'])
        ->middleware('permission:unarchive email');

    Route::delete('delete-email/{emailId}', [EmailController::class, 'deleteEmail'])
        ->middleware('permission:delete email');

    Route::post('move-to-trash/{id}', [EmailController::class, 'moveToTrash'])
        ->middleware('permission:move email to trash');

    Route::post('restore-email/{id}', [EmailController::class, 'restoreEmail'])
        ->middleware('permission:restore email');

    Route::delete('empty-trash/{id}', [EmailController::class, 'emptyTrash'])
        ->middleware('permission:empty trash');

    Route::get('trash/{userId}', [EmailController::class, 'getTrashedEmails'])
        ->middleware('permission:view trashed emails');

    Route::post('email-drafts', [EmailDraftsController::class, 'store'])
        ->middleware('permission:create drafts');

    Route::get('email-drafts/{userId}', [EmailDraftsController::class, 'index'])
        ->middleware('permission:view drafts');

    Route::get('{userId}/drafts/{draftId}', [EmailDraftsController::class, 'show'])
        ->middleware('permission:view specific draft');

    Route::delete('{userId}/drafts/{draftId}', [EmailDraftsController::class, 'destroy'])
        ->middleware('permission:delete drafts');

    Route::put('{userId}/drafts/{draftId}/restore', [EmailDraftsController::class, 'restore'])
        ->middleware('permission:restore drafts');

    Route::delete('{userId}/drafts/{draftId}/permanently', [EmailDraftsController::class, 'permanentlyDelete'])
        ->middleware('permission:permanently delete drafts');

    Route::put('{userId}/drafts/{draftId}/trash', [EmailDraftsController::class, 'moveToTrash'])
        ->middleware('permission:move draft to trash');

    Route::put('email-drafts/status', [EmailDraftsController::class, 'updateStatus'])
        ->middleware('permission:update drafts status');

    Route::put('lock/{draftId}', [EmailDraftsController::class, 'lock'])
        ->middleware('permission:lock draft');

    Route::get('history/{draftId}', [EmailDraftsController::class, 'trackHistory'])
        ->middleware('permission:track draft history');
});
