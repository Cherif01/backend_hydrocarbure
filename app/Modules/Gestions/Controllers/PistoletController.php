<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\Pistolet;
use App\Modules\Gestions\Models\Pompe;
use App\Modules\Gestions\Requests\PistoletRequest;
use App\Modules\Gestions\Resources\PistoletResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PistoletController extends Controller
{
    use ApiResponses;

    private const AUDIT_FIELDS = [
        'pompe_id',
        'hydrocarbure_id',
        'libelle',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $pistolets = Pistolet::with(['pompe.station', 'hydrocarbure', 'createdBy', 'updatedBy'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            PistoletResource::collection($pistolets),
            'Liste des pistolets chargee avec succes.'
        );
    }

    public function show(Request $request, Pistolet $pistolet, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $pistolet = $this->resolveAccessiblePistolet($pistolet->id, $scope);

        return $this->successResponse(
            new PistoletResource($pistolet),
            'Pistolet charge avec succes.'
        );
    }

    public function store(PistoletRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $this->resolveAccessiblePompe($data['pompe_id'], $scope);

        $pistolet = Pistolet::create($data);
        $pistolet->refresh()
            ->load(['pompe.station', 'hydrocarbure', 'createdBy', 'updatedBy']);

        logActivity("Creation d'un pistolet", $pistolet->only(self::AUDIT_FIELDS), $pistolet);

        return $this->successResponse(
            new PistoletResource($pistolet),
            'Pistolet cree avec succes.'
        );
    }

    public function update(PistoletRequest $request, Pistolet $pistolet, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $pistolet = $this->resolveAccessiblePistolet($pistolet->id, $scope);
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if (array_key_exists('pompe_id', $data)) {
            $this->resolveAccessiblePompe($data['pompe_id'], $scope);
        }

        $oldPistolet = $pistolet->only(self::AUDIT_FIELDS);

        $pistolet->update($data);
        $pistolet->load(['pompe.station', 'hydrocarbure', 'createdBy', 'updatedBy']);

        logActivity("Mise a jour d'un pistolet", [
            'oldPistolet' => $oldPistolet,
            'newPistolet' => $pistolet->only(self::AUDIT_FIELDS),
        ], $pistolet);

        return $this->successResponse(
            new PistoletResource($pistolet),
            'Pistolet mis a jour avec succes.'
        );
    }

    private function resolveAccessiblePistolet(int $pistoletId, array $scope): Pistolet
    {
        return Pistolet::with(['pompe.station', 'hydrocarbure', 'createdBy', 'updatedBy'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->findOrFail($pistoletId);
    }

    private function resolveAccessiblePompe(int $pompeId, array $scope): Pompe
    {
        return Pompe::when($scope['is_station_scoped'], function ($query) use ($scope) {
            $query->where('station_id', $scope['station_id']);
        })->findOrFail($pompeId);
    }
}
