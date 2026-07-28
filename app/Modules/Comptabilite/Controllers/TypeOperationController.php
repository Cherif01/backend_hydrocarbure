<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\TypeOperation;
use App\Modules\Comptabilite\Requests\TypeOperationRequest;
use App\Modules\Comptabilite\Resources\TypeOperationResource;
use App\Traits\ApiResponses;

class TypeOperationController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $typeOperations = TypeOperation::orderBy('created_at', 'desc')->get();

        return $this->successResponse(
            TypeOperationResource::collection($typeOperations),
            "Liste des types d'operation chargee avec succes."
        );
    }

    public function show(TypeOperation $typeOperation)
    {
        return $this->successResponse(
            new TypeOperationResource($typeOperation),
            "Type d'operation charge avec succes."
        );
    }

    public function store(TypeOperationRequest $request)
    {
        $data = $request->validated();

        $typeOperation = TypeOperation::create($data);

        logActivity("Creation d'un nouveau type d'operation", $typeOperation->toArray(), $typeOperation);

        return $this->successResponse(
            new TypeOperationResource($typeOperation),
            "Type d'operation cree avec succes."
        );
    }

    public function update(TypeOperationRequest $request, TypeOperation $typeOperation)
    {
        $data = $request->validated();

        $oldTypeOperation = $typeOperation->replicate()->fill($typeOperation->getAttributes());

        $typeOperation->update($data);

        logActivity("Mise a jour d'un type d'operation", [
            'oldTypeOperation' => $oldTypeOperation->toArray(),
            'newTypeOperation' => $typeOperation->toArray(),
        ], $typeOperation);

        return $this->successResponse(
            new TypeOperationResource($typeOperation),
            "Type d'operation mis a jour avec succes."
        );
    }

    public function destroy(TypeOperation $typeOperation)
    {
        logActivity("Suppression d'un type d'operation", $typeOperation->toArray(), $typeOperation);

        $typeOperation->delete();

        return $this->noContentSuccessResponse("Type d'operation supprime avec succes.");
    }
}
