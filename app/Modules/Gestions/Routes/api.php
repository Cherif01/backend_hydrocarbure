<?php

use App\Modules\Gestions\Controllers\AffectationStationController;
use App\Modules\Gestions\Controllers\AffectationPistoletController;
use App\Modules\Gestions\Controllers\ClientController;
use App\Modules\Gestions\Controllers\ClientHydrocarbureController;
use App\Modules\Gestions\Controllers\CreanceController;
use App\Modules\Gestions\Controllers\HydrocarbureController;
use App\Modules\Gestions\Controllers\PistoletController;
use App\Modules\Gestions\Controllers\PompeController;
use App\Modules\Gestions\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/gestions')->group(function () {
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('client-hydrocarbures', ClientHydrocarbureController::class);
    Route::apiResource('creances', CreanceController::class);
    Route::apiResource('affectation-pistolets', AffectationPistoletController::class);

    Route::patch('affectation-stations/{affectation_station}/switch-status', [AffectationStationController::class, 'switchStatus']);
    Route::apiResource('affectation-stations', AffectationStationController::class);

    Route::patch('stations/{station}/switch', [StationController::class, 'switchStation']);
    Route::apiResource('stations', StationController::class);
    Route::apiResource('hydrocarbures', HydrocarbureController::class)->except(['destroy']);
    Route::apiResource('pompes', PompeController::class)->except(['destroy']);
    Route::apiResource('pistolets', PistoletController::class)->except(['destroy']);
});
