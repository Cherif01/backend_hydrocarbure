<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Requests\ClientHydrocarbureRequest;
use App\Modules\Gestions\Resources\ClientHydrocarbureResource;
use App\Modules\ResourceHumaine\Models\ClientHydrocarbure;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientHydrocarbureController extends Controller
{
    use ApiResponses;

    private array $relations = ['client', 'hydrocarbure', 'createdBy', 'updatedBy'];

    public function index()
    {
        $items = ClientHydrocarbure::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            ClientHydrocarbureResource::collection($items),
            "Liste des hydrocarbures par client chargee avec succes."
        );
    }

    public function show(ClientHydrocarbure $client_hydrocarbure)
    {
        return $this->successResponse(
            new ClientHydrocarbureResource($client_hydrocarbure->load($this->relations)),
            "Affectation chargee avec succes."
        );
    }

    public function store(ClientHydrocarbureRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

        if ($isActive && $this->hasAnotherActiveDuplicate($data['client_id'], $data['hydrocarbure_id'])) {
            return $this->errorResponse("Ce client a deja une affectation active pour cet hydrocarbure.", 422);
        }

        $item = ClientHydrocarbure::create($data)->load($this->relations);

        logActivity("Creation d'une affectation client/hydrocarbure", $item->toArray(), $item);

        return $this->successResponse(
            new ClientHydrocarbureResource($item),
            "Affectation creee avec succes."
        );
    }

    public function update(ClientHydrocarbureRequest $request, ClientHydrocarbure $client_hydrocarbure)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $clientId = $data['client_id'] ?? $client_hydrocarbure->client_id;
        $hydrocarbureId = $data['hydrocarbure_id'] ?? $client_hydrocarbure->hydrocarbure_id;
        $isActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : (bool) $client_hydrocarbure->is_active;

        if ($isActive && $this->hasAnotherActiveDuplicate($clientId, $hydrocarbureId, $client_hydrocarbure->id)) {
            return $this->errorResponse("Ce client a deja une affectation active pour cet hydrocarbure.", 422);
        }

        $oldItem = $client_hydrocarbure->replicate()->fill($client_hydrocarbure->getAttributes());

        $client_hydrocarbure->update($data);
        $client_hydrocarbure->load($this->relations);

        logActivity("Mise a jour d'une affectation client/hydrocarbure", [
            'oldItem' => $oldItem->toArray(),
            'newItem' => $client_hydrocarbure->toArray(),
        ], $client_hydrocarbure);

        return $this->successResponse(
            new ClientHydrocarbureResource($client_hydrocarbure),
            "Affectation mise a jour avec succes."
        );
    }

    public function destroy(Request $request, ClientHydrocarbure $client_hydrocarbure)
    {
        logActivity("Suppression d'une affectation client/hydrocarbure", $client_hydrocarbure->toArray(), $client_hydrocarbure);

        $client_hydrocarbure->delete();

        return $this->noContentSuccessResponse("Affectation supprimee avec succes.");
    }

    private function hasAnotherActiveDuplicate(int $clientId, int $hydrocarbureId, ?int $ignoreId = null): bool
    {
        return ClientHydrocarbure::where('client_id', $clientId)
            ->where('hydrocarbure_id', $hydrocarbureId)
            ->where('is_active', true)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }
}
