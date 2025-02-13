<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::get('/user',[UserController::class, 'index'])->middleware('permission:user-index');
    Route::get('/user/{id}',[UserController::class, 'show'])->middleware('permission:user-show');
    Route::delete('/user/{id}',[UserController::class, 'destroy'])->middleware('permission:user-delete');
});