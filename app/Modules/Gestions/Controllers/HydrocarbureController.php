<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\Hydrocarbure;
use App\Modules\Gestions\Requests\HydrocarbureRequest;
use App\Modules\Gestions\Resources\HydrocarbureResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;

class HydrocarbureController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $hydrocarbures = Hydrocarbure::with(['createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            HydrocarbureResource::collection($hydrocarbures),
            'Liste des hydrocarbures chargee avec succes.'
        );
    }

    public function show(Hydrocarbure $hydrocarbure)
    {
        return $this->successResponse(
            new HydrocarbureResource($hydrocarbure->load(['createdBy', 'updatedBy'])),
            'Hydrocarbure charge avec succes.'
        );
    }

    public function store(HydrocarbureRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $hydrocarbure = Hydrocarbure::create($data)->load(['createdBy', 'updatedBy']);

        logActivity("Creation d'un hydrocarbure", $hydrocarbure->toArray(), $hydrocarbure);

        return $this->successResponse(
            new HydrocarbureResource($hydrocarbure),
            'Hydrocarbure cree avec succes.'
        );
    }

    public function update(HydrocarbureRequest $request, Hydrocarbure $hydrocarbure)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();
        $auditFields = ['libelle', 'prix_achat', 'prix_vente', 'created_by', 'updated_by'];
        $oldHydrocarbure = $hydrocarbure->only($auditFields);

        $hydrocarbure->update($data);
        $hydrocarbure->load(['createdBy', 'updatedBy']);

        logActivity("Mise a jour d'un hydrocarbure", [
            'oldHydrocarbure' => $oldHydrocarbure,
            'newHydrocarbure' => $hydrocarbure->only($auditFields),
        ], $hydrocarbure);

        return $this->successResponse(
            new HydrocarbureResource($hydrocarbure),
            'Hydrocarbure mis a jour avec succes.'
        );
    }
}
