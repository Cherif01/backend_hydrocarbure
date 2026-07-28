<?php

use App\Modules\Comptabilite\Controllers\CaisseController;
use App\Modules\Comptabilite\Controllers\OperationController;
use App\Modules\Comptabilite\Controllers\PaiementCreanceController;
use App\Modules\Comptabilite\Controllers\TypeOperationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/comptabilite')->group(function () {
    Route::apiResource('type-operations', TypeOperationController::class);
    Route::apiResource('caisses', CaisseController::class)->parameters(['caisses' => 'caisse']);
    Route::apiResource('operations', OperationController::class);
    Route::apiResource('paiement-creances', PaiementCreanceController::class)
        ->parameters(['paiement-creances' => 'paiement_creance']);
});
