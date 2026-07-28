<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Requests\ClientRequest;
use App\Modules\Gestions\Resources\ClientResource;
use App\Modules\ResourceHumaine\Models\Client;
use App\Modules\ResourceHumaine\Models\ClientHydrocarbure;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    use ApiResponses, CloudflareUpload;

    private array $relations = ['createdBy', 'updatedBy', 'hydrocarbures.hydrocarbure'];

    public function index()
    {
        $clients = Client::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            ClientResource::collection($clients),
            "Liste des clients chargee avec succes."
        );
    }

    public function show(Client $client)
    {
        return $this->successResponse(
            new ClientResource($client->load($this->relations)),
            "Client charge avec succes."
        );
    }

    public function store(ClientRequest $request)
    {
        $data = $request->validated();
        $hydrocarbures = $data['hydrocarbure'] ?? [];
        unset($data['hydrocarbure']);
        $data['created_by'] = Auth::id();

        $duplicateError = $this->validateDuplicateHydrocarbures($hydrocarbures);
        if ($duplicateError) {
            return $this->errorResponse($duplicateError, 422);
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->uploadImage($request->file('avatar'), 'clients');
        }

        $client = DB::transaction(function () use ($data, $hydrocarbures) {
            $client = Client::create($data);

            foreach ($hydrocarbures as $item) {
                ClientHydrocarbure::create([
                    'client_id' => $client->id,
                    'hydrocarbure_id' => (int) $item['hydrocarbure_id'],
                    'max_litre' => $item['max_litre'] ?? 0,
                    'prix' => $item['prix'] ?? 0,
                    'is_active' => true,
                    'created_by' => Auth::id(),
                ]);
            }

            return $client->load($this->relations);
        });

        logActivity("Creation d'un nouveau client", $client->toArray(), $client);

        return $this->successResponse(
            new ClientResource($client),
            "Client cree avec succes."
        );
    }

    public function update(ClientRequest $request, Client $client)
    {
        $data = $request->validated();
        $hydrocarbures = $data['hydrocarbure'] ?? null;
        unset($data['hydrocarbure']);
        $data['updated_by'] = Auth::id();

        if (is_array($hydrocarbures)) {
            $duplicateError = $this->validateDuplicateHydrocarbures($hydrocarbures);
            if ($duplicateError) {
                return $this->errorResponse($duplicateError, 422);
            }
        }

        if ($request->hasFile('avatar')) {
            if ($client->avatar) {
                $this->deleteImage($client->avatar, 'clients');
            }

            $data['avatar'] = $this->uploadImage($request->file('avatar'), 'clients');
        }

        $oldClient = $client->replicate()->fill($client->getAttributes());

        $client = DB::transaction(function () use ($client, $data, $hydrocarbures) {
            $client->update($data);

            if (is_array($hydrocarbures)) {
                foreach ($hydrocarbures as $item) {
                    $hydrocarbureId = (int) $item['hydrocarbure_id'];

                    $existing = ClientHydrocarbure::where('client_id', $client->id)
                        ->where('hydrocarbure_id', $hydrocarbureId)
                        ->orderBy('id', 'desc')
                        ->first();

                    $hasOtherActive = ClientHydrocarbure::where('client_id', $client->id)
                        ->where('hydrocarbure_id', $hydrocarbureId)
                        ->where('is_active', true)
                        ->when($existing, function ($query) use ($existing) {
                            $query->where('id', '!=', $existing->id);
                        })
                        ->exists();

                    if ($hasOtherActive) {
                        throw ValidationException::withMessages([
                            'hydrocarbure' => "Ce client a deja une affectation active pour cet hydrocarbure.",
                        ]);
                    }

                    if ($existing) {
                        $existing->update([
                            'max_litre' => $item['max_litre'] ?? $existing->max_litre,
                            'prix' => $item['prix'] ?? $existing->prix,
                            'is_active' => true,
                            'updated_by' => Auth::id(),
                        ]);
                    } else {
                        ClientHydrocarbure::create([
                            'client_id' => $client->id,
                            'hydrocarbure_id' => $hydrocarbureId,
                            'max_litre' => $item['max_litre'] ?? 0,
                            'prix' => $item['prix'] ?? 0,
                            'is_active' => true,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            return $client->load($this->relations);
        });

        logActivity("Mise a jour d'un client", [
            'oldClient' => $oldClient->toArray(),
            'newClient' => $client->toArray(),
        ], $client);

        return $this->successResponse(
            new ClientResource($client),
            "Client mis a jour avec succes."
        );
    }

    public function destroy(Request $request, Client $client)
    {
        if ($client->avatar) {
            $this->deleteImage($client->avatar, 'clients');
        }

        logActivity("Suppression d'un client", $client->toArray(), $client);

        $client->delete();

        return $this->noContentSuccessResponse("Client supprime avec succes.");
    }

    private function validateDuplicateHydrocarbures(array $hydrocarbures): ?string
    {
        $ids = [];

        foreach ($hydrocarbures as $item) {
            if (! array_key_exists('hydrocarbure_id', $item)) {
                continue;
            }

            $ids[] = (int) $item['hydrocarbure_id'];
        }

        if (count($ids) !== count(array_unique($ids))) {
            return "Vous ne pouvez pas selectionner deux fois le meme hydrocarbure pour le meme client.";
        }

        return null;
    }
}
