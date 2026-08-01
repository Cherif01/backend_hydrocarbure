<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\ClientDepot;
use App\Modules\Comptabilite\Requests\ClientDepotRequest;
use App\Modules\Comptabilite\Resources\ClientDepotResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClientDepotController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'client',
        'createdBy',
        'updatedBy',
    ];

    public function index()
    {
        $depots = ClientDepot::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            ClientDepotResource::collection($depots),
            'Liste des depots clients chargee avec succes.'
        );
    }

    public function show(ClientDepot $client_depot)
    {
        $client_depot->load($this->relations);

        return $this->successResponse(
            new ClientDepotResource($client_depot),
            'Depot client charge avec succes.'
        );
    }

    public function store(ClientDepotRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if (! array_key_exists('reference', $data) || $data['reference'] === null || $data['reference'] === '') {
            $data['reference'] = $this->generateUniqueReference();
        }

        $depot = ClientDepot::create($data)->load($this->relations);

        logActivity("Creation d'un depot client", $depot->toArray(), $depot);

        return $this->successResponse(
            new ClientDepotResource($depot),
            'Depot client cree avec succes.'
        );
    }

    public function update(ClientDepotRequest $request, ClientDepot $client_depot)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if (array_key_exists('reference', $data) && ($data['reference'] === null || $data['reference'] === '')) {
            unset($data['reference']);
        }

        $oldDepot = $client_depot->replicate()->fill($client_depot->getAttributes());

        $client_depot->update($data);
        $client_depot->load($this->relations);

        logActivity("Mise a jour d'un depot client", [
            'oldClientDepot' => $oldDepot->toArray(),
            'newClientDepot' => $client_depot->toArray(),
        ], $client_depot);

        return $this->successResponse(
            new ClientDepotResource($client_depot),
            'Depot client mis a jour avec succes.'
        );
    }

    public function destroy(ClientDepot $client_depot)
    {
        $client_depot->load($this->relations);

        logActivity("Suppression d'un depot client", $client_depot->toArray(), $client_depot);

        $client_depot->delete();

        return $this->noContentSuccessResponse('Depot client supprime avec succes.');
    }

    private function generateUniqueReference(): string
    {
        do {
            $reference = 'CLDEP-' . Str::upper(Str::random(6));
        } while (ClientDepot::where('reference', $reference)->exists());

        return $reference;
    }
}

