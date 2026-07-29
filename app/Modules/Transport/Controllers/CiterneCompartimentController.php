<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\CiterneCompartiment;
use App\Modules\Transport\Requests\CiterneCompartimentRequest;
use App\Modules\Transport\Resources\CiterneCompartimentResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;

class CiterneCompartimentController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'citerne',
        'hydrocarbure',
        'createdBy',
        'updatedBy',
    ];

    public function index()
    {
        $compartiments = CiterneCompartiment::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            CiterneCompartimentResource::collection($compartiments),
            "Liste des compartiments de citerne chargee avec succes."
        );
    }

    public function show(CiterneCompartiment $citerne_compartiment)
    {
        $citerne_compartiment->load($this->relations);

        return $this->successResponse(
            new CiterneCompartimentResource($citerne_compartiment),
            "Compartiment de citerne charge avec succes."
        );
    }

    public function store(CiterneCompartimentRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $compartiment = CiterneCompartiment::create($data)->load($this->relations);

        logActivity("Creation d'un compartiment de citerne", $compartiment->toArray(), $compartiment);

        return $this->successResponse(
            new CiterneCompartimentResource($compartiment),
            "Compartiment de citerne cree avec succes."
        );
    }

    public function update(CiterneCompartimentRequest $request, CiterneCompartiment $citerne_compartiment)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $oldCompartiment = $citerne_compartiment->replicate()->fill($citerne_compartiment->getAttributes());

        $citerne_compartiment->update($data);
        $citerne_compartiment->load($this->relations);

        logActivity("Mise a jour d'un compartiment de citerne", [
            'oldCompartiment' => $oldCompartiment->toArray(),
            'newCompartiment' => $citerne_compartiment->toArray(),
        ], $citerne_compartiment);

        return $this->successResponse(
            new CiterneCompartimentResource($citerne_compartiment),
            "Compartiment de citerne mis a jour avec succes."
        );
    }

    public function destroy(CiterneCompartiment $citerne_compartiment)
    {
        $citerne_compartiment->load($this->relations);

        logActivity("Suppression d'un compartiment de citerne", $citerne_compartiment->toArray(), $citerne_compartiment);

        $citerne_compartiment->delete();

        return $this->noContentSuccessResponse("Compartiment de citerne supprime avec succes.");
    }
}

