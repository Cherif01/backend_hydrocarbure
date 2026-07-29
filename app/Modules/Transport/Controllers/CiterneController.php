<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\Citerne;
use App\Modules\Transport\Requests\CiterneRequest;
use App\Modules\Transport\Resources\CiterneResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;

class CiterneController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'compartiments.hydrocarbure',
        'documents',
        'maintenances',
        'createdBy',
        'updatedBy',
    ];

    public function index()
    {
        $citernes = Citerne::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            CiterneResource::collection($citernes),
            "Liste des citernes chargee avec succes."
        );
    }

    public function show(Citerne $citerne)
    {
        $citerne->load($this->relations);

        return $this->successResponse(
            new CiterneResource($citerne),
            "Citerne chargee avec succes."
        );
    }

    public function store(CiterneRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $citerne = Citerne::create($data)->load($this->relations);

        logActivity("Creation d'une citerne", $citerne->toArray(), $citerne);

        return $this->successResponse(
            new CiterneResource($citerne),
            "Citerne creee avec succes."
        );
    }

    public function update(CiterneRequest $request, Citerne $citerne)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $oldCiterne = $citerne->replicate()->fill($citerne->getAttributes());

        $citerne->update($data);
        $citerne->load($this->relations);

        logActivity("Mise a jour d'une citerne", [
            'oldCiterne' => $oldCiterne->toArray(),
            'newCiterne' => $citerne->toArray(),
        ], $citerne);

        return $this->successResponse(
            new CiterneResource($citerne),
            "Citerne mise a jour avec succes."
        );
    }

    public function destroy(Citerne $citerne)
    {
        $citerne->load($this->relations);

        logActivity("Suppression d'une citerne", $citerne->toArray(), $citerne);

        $citerne->delete();

        return $this->noContentSuccessResponse("Citerne supprimee avec succes.");
    }
}

