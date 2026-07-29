<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\Cuve;
use App\Modules\Gestions\Models\CuveJaugeage;
use App\Modules\Gestions\Requests\CuveJaugeageRequest;
use App\Modules\Gestions\Resources\CuveJaugeageResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CuveJaugeageController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'cuve.station',
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

        $jaugeages = CuveJaugeage::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('cuve', function ($cuveQuery) use ($scope) {
                    $cuveQuery->where('station_id', $scope['station_id']);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            CuveJaugeageResource::collection($jaugeages),
            "Liste des jaugeages de cuve chargee avec succes."
        );
    }

    public function show(Request $request, CuveJaugeage $cuve_jaugeage, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $jaugeage = $this->resolveAccessibleJaugeage((int) $cuve_jaugeage->id, $scope);

        return $this->successResponse(
            new CuveJaugeageResource($jaugeage),
            "Jaugeage de cuve charge avec succes."
        );
    }

    public function store(CuveJaugeageRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if ($scope['is_station_scoped']) {
            $isCuveAllowed = Cuve::where('id', $data['cuve_id'])
                ->where('station_id', $scope['station_id'])
                ->exists();

            if (! $isCuveAllowed) {
                return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
            }
        }

        $jaugeage = CuveJaugeage::create($data)->load($this->relations);

        logActivity("Creation d'un jaugeage de cuve", $jaugeage->toArray(), $jaugeage);

        return $this->successResponse(
            new CuveJaugeageResource($jaugeage),
            "Jaugeage de cuve cree avec succes."
        );
    }

    public function update(CuveJaugeageRequest $request, CuveJaugeage $cuve_jaugeage, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $jaugeage = $this->resolveAccessibleJaugeage((int) $cuve_jaugeage->id, $scope);

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if ($scope['is_station_scoped']) {
            $cuveId = (int) ($data['cuve_id'] ?? $jaugeage->cuve_id);

            $isCuveAllowed = Cuve::where('id', $cuveId)
                ->where('station_id', $scope['station_id'])
                ->exists();

            if (! $isCuveAllowed) {
                return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
            }
        }

        $oldJaugeage = $jaugeage->replicate()->fill($jaugeage->getAttributes());

        $jaugeage->update($data);
        $jaugeage->load($this->relations);

        logActivity("Mise a jour d'un jaugeage de cuve", [
            'oldCuveJaugeage' => $oldJaugeage->toArray(),
            'newCuveJaugeage' => $jaugeage->toArray(),
        ], $jaugeage);

        return $this->successResponse(
            new CuveJaugeageResource($jaugeage),
            "Jaugeage de cuve mis a jour avec succes."
        );
    }

    public function destroy(Request $request, CuveJaugeage $cuve_jaugeage, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $jaugeage = $this->resolveAccessibleJaugeage((int) $cuve_jaugeage->id, $scope);

        logActivity("Suppression d'un jaugeage de cuve", $jaugeage->toArray(), $jaugeage);

        $jaugeage->delete();

        return $this->noContentSuccessResponse("Jaugeage de cuve supprime avec succes.");
    }

    private function resolveScope(Request $request, UserStationScopeService $stationScopeService): array
    {
        $user = $request->user();

        if (in_array($user?->role, ['super_admin', 'admin'], true)) {
            return [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        }

        $hasGerantStation = $user?->userModules()
            ->where('is_active', true)
            ->whereHas('module', function ($query) {
                $query->where('name', 'gerant_station')
                    ->where('is_active', true);
            })
            ->exists() ?? false;

        if (! $hasGerantStation) {
            throw new AuthorizationException("Vous n'avez pas la permission d'effectuer cette operation.");
        }

        return $stationScopeService->resolve($user);
    }

    private function resolveAccessibleJaugeage(int $jaugeageId, array $scope): CuveJaugeage
    {
        return CuveJaugeage::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('cuve', function ($cuveQuery) use ($scope) {
                    $cuveQuery->where('station_id', $scope['station_id']);
                });
            })
            ->findOrFail($jaugeageId);
    }
}

