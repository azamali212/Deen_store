<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::post('register', [AuthController::class, 'register']); 
Route::get('email/verify/{token}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/user-login', [AuthController::class, 'login']);