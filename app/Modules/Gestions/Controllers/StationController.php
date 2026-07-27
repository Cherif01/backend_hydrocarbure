<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\Station;
use App\Modules\Gestions\Requests\StationRequest;
use App\Modules\Gestions\Resources\StationResource;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StationController extends Controller
{
    use ApiResponses, CloudflareUpload;

    public function index()
    {
        $stations = Station::with(['createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            StationResource::collection($stations),
            "Liste des stations chargee avec succes."
        );
    }

    public function show(Station $station)
    {
        return $this->successResponse(
            new StationResource($station->load(['createdBy', 'updatedBy'])),
            "Station chargee avec succes."
        );
    }

    public function store(StationRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['reference'] = $data['reference'] ?? $this->generateUniqueReference();

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage($request->file('image'), 'stations');
        }

        $station = Station::create($data)->load(['createdBy', 'updatedBy']);

        logActivity("Creation d'une nouvelle station", $station->toArray(), $station);

        return $this->successResponse(
            new StationResource($station),
            "Station creee avec succes."
        );
    }

    public function update(StationRequest $request, Station $station)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if (empty($data['reference'])) {
            unset($data['reference']);
        }

        if ($request->hasFile('image')) {
            if ($station->image) {
                $this->deleteImage($station->image, 'stations');
            }

            $data['image'] = $this->uploadImage($request->file('image'), 'stations');
        }

        $oldStation = $station->replicate()->fill($station->getAttributes());

        $station->update($data);
        $station->load(['createdBy', 'updatedBy']);

        logActivity("Mise a jour d'une station", [
            'oldStation' => $oldStation->toArray(),
            'newStation' => $station->toArray(),
        ], $station);

        return $this->successResponse(
            new StationResource($station),
            "Station mise a jour avec succes."
        );
    }

    public function switchStation(Station $station)
    {
        $station->is_active = !$station->is_active;
        $station->updated_by = Auth::id();
        $station->save();
        $station->load(['createdBy', 'updatedBy']);

        logActivity("Changement de statut d'une station", $station->toArray(), $station);

        return $this->successResponse(
            new StationResource($station),
            "Statut de la station change avec succes."
        );
    }

    public function destroy(Station $station)
    {
        if ($station->image) {
            $this->deleteImage($station->image, 'stations');
        }

        logActivity("Suppression d'une station", $station->toArray(), $station);

        $station->delete();

        return $this->noContentSuccessResponse("Station supprimee avec succes.");
    }

    private function generateUniqueReference(): string
    {
        do {
            $reference = 'STA' . Str::upper(Str::random(6));
        } while (Station::where('reference', $reference)->exists());

        return $reference;
    }
}
