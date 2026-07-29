<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\ApproCompartimentJauge;
use App\Modules\Transport\Models\Approvision;
use App\Modules\Transport\Requests\ApproCompartimentJaugeRequest;
use App\Modules\Transport\Resources\ApproCompartimentJaugeResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApproCompartimentJaugeController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'approvision.station',
        'hydrocarbure',
        'createdBy',
        'updatedBy',
    ];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $jauges = ApproCompartimentJauge::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('approvision', function ($approQuery) use ($scope) {
                    $approQuery->where('station_id', $scope['station_id']);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            ApproCompartimentJaugeResource::collection($jauges),
            "Liste des jauges d'approvisionnement chargee avec succes."
        );
    }

    public function show(Request $request, ApproCompartimentJauge $appro_compartiment_jauge, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $jauge = $this->resolveAccessibleJauge((int) $appro_compartiment_jauge->id, $scope);

        return $this->successResponse(
            new ApproCompartimentJaugeResource($jauge),
            "Jauge d'approvisionnement chargee avec succes."
        );
    }

    public function store(ApproCompartimentJaugeRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $approvision = $this->resolveAccessibleApprovision((int) $data['approvision_id'], $scope);
        if (! $approvision) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $jauge = ApproCompartimentJauge::create($data)->load($this->relations);

        logActivity("Creation d'une jauge d'approvisionnement", $jauge->toArray(), $jauge);

        return $this->successResponse(
            new ApproCompartimentJaugeResource($jauge),
            "Jauge d'approvisionnement creee avec succes."
        );
    }

    public function update(ApproCompartimentJaugeRequest $request, ApproCompartimentJauge $appro_compartiment_jauge, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $jauge = $this->resolveAccessibleJauge((int) $appro_compartiment_jauge->id, $scope);

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $approvisionId = (int) ($data['approvision_id'] ?? $jauge->approvision_id);
        $approvision = $this->resolveAccessibleApprovision($approvisionId, $scope);
        if (! $approvision) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $oldJauge = $jauge->replicate()->fill($jauge->getAttributes());

        $jauge->update($data);
        $jauge->load($this->relations);

        logActivity("Mise a jour d'une jauge d'approvisionnement", [
            'oldJauge' => $oldJauge->toArray(),
            'newJauge' => $jauge->toArray(),
        ], $jauge);

        return $this->successResponse(
            new ApproCompartimentJaugeResource($jauge),
            "Jauge d'approvisionnement mise a jour avec succes."
        );
    }

    public function destroy(Request $request, ApproCompartimentJauge $appro_compartiment_jauge, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $jauge = $this->resolveAccessibleJauge((int) $appro_compartiment_jauge->id, $scope);

        logActivity("Suppression d'une jauge d'approvisionnement", $jauge->toArray(), $jauge);

        $jauge->delete();

        return $this->noContentSuccessResponse("Jauge d'approvisionnement supprimee avec succes.");
    }

    private function resolveScope(Request $request, UserStationScopeService $stationScopeService): array
    {
        $user = $request->user();

        if ($this->isAdmin($user)) {
            return [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        }

        if (! $this->hasActiveModule($user, 'gerant_station')) {
            throw new AuthorizationException("Vous n'avez pas la permission d'effectuer cette operation.");
        }

        return $stationScopeService->resolve($user);
    }

    private function resolveAccessibleApprovision(int $approvisionId, array $scope): ?Approvision
    {
        return Approvision::query()
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->find($approvisionId);
    }

    private function resolveAccessibleJauge(int $jaugeId, array $scope): ApproCompartimentJauge
    {
        return ApproCompartimentJauge::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('approvision', function ($approQuery) use ($scope) {
                    $approQuery->where('station_id', $scope['station_id']);
                });
            })
            ->findOrFail($jaugeId);
    }

    private function isAdmin($user): bool
    {
        return in_array($user?->role, ['super_admin', 'admin'], true);
    }

    private function hasActiveModule($user, string $moduleName): bool
    {
        return $user?->userModules()
            ->where('is_active', true)
            ->whereHas('module', function ($query) use ($moduleName) {
                $query->where('name', $moduleName)
                    ->where('is_active', true);
            })
            ->exists() ?? false;
    }
}

