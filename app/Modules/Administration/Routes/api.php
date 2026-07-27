<?php

use App\Modules\Administration\Controllers\AuthController;
use App\Modules\Administration\Controllers\ModuleController;
use App\Modules\Administration\Controllers\UserModuleController;
use Illuminate\Support\Facades\Route;

// Define API routes for Administration module here
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum'])->prefix('v1/auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::patch('/switch-status/{user_id}', [AuthController::class, 'switchStatus']);
    Route::get('/users', [AuthController::class, 'users']);

    Route::apiResource('modules', ModuleController::class);
    Route::patch('/switch-status/{user_module_id}', [UserModuleController::class, 'switchStatus']);
    Route::post('/send-access-code/{user_module_id}', [UserModuleController::class, 'sendAccessCode']);
    Route::post('/verify-access-code', [UserModuleController::class, 'verifyAccessCode']);
    Route::apiResource('user-modules', UserModuleController::class);
});
