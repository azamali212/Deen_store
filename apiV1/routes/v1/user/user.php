<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::get('/user/recycleBinUsers',[UserController::class, 'recycleBinUsers'])->name('recycleBinUsers');
    Route::get('/user',[UserController::class, 'getAllUsers'])->middleware('permission:user-index');
    Route::get('/user/{id}',[UserController::class, 'show'])->middleware('permission:user-show');
    Route::post('/user',[UserController::class, 'createUser'])->middleware('permission:user-create');
    Route::delete('/user/{id}',[UserController::class, 'deleteUser'])->middleware('permission:user-delete');
    Route::put('/user/{id}',[UserController::class, 'updateUser'])->middleware('permission:user-edit');
    Route::get('/user/restore/{id}',[UserController::class, 'restoreUser']);//->middleware('permission:user-restore');
    Route::delete('/user/{id}/permanentDelete',[UserController::class, 'forceDeleteUser']);//->middleware('permission:user-permissions')
});