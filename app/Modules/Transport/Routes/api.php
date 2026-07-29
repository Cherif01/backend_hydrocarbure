<?php

use App\Modules\Transport\Controllers\AffectationCiterneController;
use App\Modules\Transport\Controllers\AffectationCiterneDepenseController;
use App\Modules\Transport\Controllers\ApproCompartimentJaugeController;
use App\Modules\Transport\Controllers\ApprovisionController;
use App\Modules\Transport\Controllers\CiterneCompartimentController;
use App\Modules\Transport\Controllers\CiterneController;
use App\Modules\Transport\Controllers\CiterneDocumentController;
use App\Modules\Transport\Controllers\MaintenanceCiterneController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/transport')->group(function () {
    Route::apiResource('citernes', CiterneController::class);

    Route::apiResource('affectation-citernes', AffectationCiterneController::class)
        ->parameters(['affectation-citernes' => 'affectation_citerne']);

    Route::apiResource('affectation-citerne-depenses', AffectationCiterneDepenseController::class)
        ->parameters(['affectation-citerne-depenses' => 'affectation_citerne_depense']);

    Route::apiResource('approvisions', ApprovisionController::class);

    Route::apiResource('appro-compartiment-jauges', ApproCompartimentJaugeController::class)
        ->parameters(['appro-compartiment-jauges' => 'appro_compartiment_jauge']);

    Route::apiResource('citerne-compartiments', CiterneCompartimentController::class)
        ->parameters(['citerne-compartiments' => 'citerne_compartiment']);

    Route::apiResource('citerne-documents', CiterneDocumentController::class)
        ->parameters(['citerne-documents' => 'citerne_document']);

    Route::apiResource('maintenances-citerne', MaintenanceCiterneController::class)
        ->parameters(['maintenances-citerne' => 'maintenance_citerne']);
});
