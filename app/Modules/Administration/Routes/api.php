<?php

use App\Modules\Administration\Controllers\AuthController;
use App\Modules\Administration\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// Define API routes for Administration module here
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->prefix('v1/auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::put('/switch-role/{user_id}', [AuthController::class, 'switchRole']);
    Route::patch('/switch-status/{user_id}', [AuthController::class, 'switchStatus']);
    Route::get('/users', [AuthController::class, 'users']);

    Route::apiResource('roles', RoleController::class);
});
