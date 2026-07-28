<?php

use App\Modules\Gestions\Controllers\AffectationStationController;
use App\Modules\Gestions\Controllers\HydrocarbureController;
use App\Modules\Gestions\Controllers\PistoletController;
use App\Modules\Gestions\Controllers\PompeController;
use App\Modules\Gestions\Controllers\StationController;
use App\Modules\Gestions\Middleware\EnsureGestionsAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/gestions')->group(function () {
    Route::patch('affectation-stations/{affectation_station}/switch-status', [AffectationStationController::class, 'switchStatus']);
    Route::apiResource('affectation-stations', AffectationStationController::class);

    Route::patch('stations/{station}/switch', [StationController::class, 'switchStation']);
    Route::apiResource('stations', StationController::class);
});

Route::middleware(['auth:sanctum', EnsureGestionsAccess::class])
    ->prefix('v1/gestions')
    ->group(function () {
        Route::apiResource('hydrocarbures', HydrocarbureController::class)
            ->only(['index', 'show']);
        Route::apiResource('pompes', PompeController::class)
            ->except(['destroy']);
        Route::apiResource('pistolets', PistoletController::class)
            ->except(['destroy']);
    });

Route::middleware(['auth:sanctum', EnsureGestionsAccess::class.':admin'])
    ->prefix('v1/gestions')
    ->group(function () {
        Route::apiResource('hydrocarbures', HydrocarbureController::class)
            ->only(['store', 'update']);
    });
