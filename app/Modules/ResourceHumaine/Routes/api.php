<?php

use App\Modules\ResourceHumaine\Controllers\EmployeeController;
use App\Modules\ResourceHumaine\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/rh')->group(function () {
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('posts', PostController::class);
});
