<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\Caisse;
use App\Modules\Comptabilite\Models\Operation;
use App\Modules\Comptabilite\Requests\OperationRequest;
use App\Modules\Comptabilite\Resources\OperationResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperationController extends Controller
{
    use ApiResponses;

    private array $relations = ['typeOperation', 'caisse', 'station', 'createdBy', 'updatedBy'];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $operations = Operation::with($this->relations)
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->orderBy('date_operation', 'desc')
            ->get();

        return $this->successResponse(
            OperationResource::collection($operations),
            "Liste des operations chargee avec succes."
        );
    }

    public function show(Request $request, Operation $operation, UserStationScopeService $stationScopeService)
    {
        try {
            $operation = $this->resolveScopedOperation($request, $operation->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        return $this->successResponse(
            new OperationResource($operation),
            "Operation chargee avec succes."
        );
    }

    public function store(OperationRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();

        $error = $this->applyStationCaisseConsistency($data, $scope);
        if ($error) {
            return $this->errorResponse($error, 422);
        }

        $data['created_by'] = Auth::id();

        $operation = Operation::create($data)->load($this->relations);

        logActivity("Creation d'une nouvelle operation", $operation->toArray(), $operation);

        return $this->successResponse(
            new OperationResource($operation),
            "Operation creee avec succes."
        );
    }

    public function update(OperationRequest $request, Operation $operation, UserStationScopeService $stationScopeService)
    {
        try {
            $operation = $this->resolveScopedOperation($request, $operation->id, $stationScopeService);
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();

        $error = $this->applyStationCaisseConsistency($data, $scope, $operation->station_id);
        if ($error) {
            return $this->errorResponse($error, 422);
        }

        $data['updated_by'] = Auth::id();

        $oldOperation = $operation->replicate()->fill($operation->getAttributes());

        $operation->update($data);
        $operation->load($this->relations);

        logActivity("Mise a jour d'une operation", [
            'oldOperation' => $oldOperation->toArray(),
            'newOperation' => $operation->toArray(),
        ], $operation);

        return $this->successResponse(
            new OperationResource($operation),
            "Operation mise a jour avec succes."
        );
    }

    public function destroy(Request $request, Operation $operation, UserStationScopeService $stationScopeService)
    {
        try {
            $operation = $this->resolveScopedOperation($request, $operation->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        logActivity("Suppression d'une operation", $operation->toArray(), $operation);

        $operation->delete();

        return $this->noContentSuccessResponse("Operation supprimee avec succes.");
    }

    private function resolveScopedOperation(Request $request, int $operationId, UserStationScopeService $stationScopeService): Operation
    {
        $scope = $stationScopeService->resolve($request->user());

        return Operation::with($this->relations)
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($operationId);
    }

    /**
     * Force la station selon le scope de l'utilisateur et verifie la coherence caisse/station.
     * Retourne un message d'erreur en cas d'incoherence, null sinon.
     */
    private function applyStationCaisseConsistency(array &$data, array $scope, ?int $fallbackStationId = null): ?string
    {
        if ($scope['station_id']) {
            $data['station_id'] = $scope['station_id'];
        } else {
            $data['station_id'] = $data['station_id'] ?? $fallbackStationId;
        }

        if (! empty($data['caisse_id'])) {
            $caisse = Caisse::find($data['caisse_id']);

            if (! $caisse) {
                return "La caisse selectionnee est introuvable.";
            }

            if ($data['station_id']) {
                if ((int) $caisse->station_id !== (int) $data['station_id']) {
                    return "La caisse selectionnee n'appartient pas a la station indiquee.";
                }
            } else {
                $data['station_id'] = $caisse->station_id;
            }
        }

        return null;
    }
}
