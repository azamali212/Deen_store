<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerManagement\CustomerController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::get('/customer/getAll',[CustomerController::class, 'getAllCustomers']);//->middleware('permission:customer-index');
    Route::get('/customer/getById/{id}',[CustomerController::class, 'getCustomerById']);//->middleware('permission:customer-show');
    Route::post('/customer/create',[CustomerController::class, 'createCustomer']);//->middleware('permission:customer-create');
    Route::put('/customer/update/{id}',[CustomerController::class, 'updateCustomer']);//->middleware('permission:customer-update');
    Route::delete('/customer/delete/{id}',[CustomerController::class, 'deleteCustomer']);//->middleware('permission:customer-delete');
});