<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\Caisse;
use App\Modules\Comptabilite\Requests\CaisseRequest;
use App\Modules\Comptabilite\Resources\CaisseResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaisseController extends Controller
{
    use ApiResponses;

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $caisses = Caisse::with(['station', 'createdBy', 'updatedBy'])
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            CaisseResource::collection($caisses),
            "Liste des caisses chargee avec succes."
        );
    }

    public function show(Request $request, Caisse $caisse, UserStationScopeService $stationScopeService)
    {
        try {
            $caisse = $this->resolveScopedCaisse($request, $caisse->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        return $this->successResponse(
            new CaisseResource($caisse),
            "Caisse chargee avec succes."
        );
    }

    public function store(CaisseRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();

        if ($scope['station_id']) {
            $data['station_id'] = $scope['station_id'];
        } elseif (empty($data['station_id'])) {
            return $this->errorResponse("La station est obligatoire.", 422);
        }

        $data['created_by'] = Auth::id();

        $caisse = Caisse::create($data)->load(['station', 'createdBy', 'updatedBy']);

        logActivity("Creation d'une nouvelle caisse", $caisse->toArray(), $caisse);

        return $this->successResponse(
            new CaisseResource($caisse),
            "Caisse creee avec succes."
        );
    }

    public function update(CaisseRequest $request, Caisse $caisse, UserStationScopeService $stationScopeService)
    {
        try {
            $caisse = $this->resolveScopedCaisse($request, $caisse->id, $stationScopeService);
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();

        if ($scope['station_id']) {
            $data['station_id'] = $scope['station_id'];
        }

        $data['updated_by'] = Auth::id();

        $oldCaisse = $caisse->replicate()->fill($caisse->getAttributes());

        $caisse->update($data);
        $caisse->load(['station', 'createdBy', 'updatedBy']);

        logActivity("Mise a jour d'une caisse", [
            'oldCaisse' => $oldCaisse->toArray(),
            'newCaisse' => $caisse->toArray(),
        ], $caisse);

        return $this->successResponse(
            new CaisseResource($caisse),
            "Caisse mise a jour avec succes."
        );
    }

    public function destroy(Request $request, Caisse $caisse, UserStationScopeService $stationScopeService)
    {
        try {
            $caisse = $this->resolveScopedCaisse($request, $caisse->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        logActivity("Suppression d'une caisse", $caisse->toArray(), $caisse);

        $caisse->delete();

        return $this->noContentSuccessResponse("Caisse supprimee avec succes.");
    }

    private function resolveScopedCaisse(Request $request, int $caisseId, UserStationScopeService $stationScopeService): Caisse
    {
        $scope = $stationScopeService->resolve($request->user());

        return Caisse::with(['station', 'createdBy', 'updatedBy'])
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($caisseId);
    }
}
