<?php

use App\Modules\Gestions\Controllers\AffectationStationController;
use App\Modules\Gestions\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/gestions')->group(function () {
    Route::patch('affectation-stations/{affectation_station}/switch-status', [AffectationStationController::class, 'switchStatus']);
    Route::apiResource('affectation-stations', AffectationStationController::class);

    Route::patch('stations/{station}/switch', [StationController::class, 'switchStation']);
    Route::apiResource('stations', StationController::class);
});
