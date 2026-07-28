<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\AffectationPistolet;
use App\Modules\Gestions\Models\Creance;
use App\Modules\Gestions\Requests\CreanceRequest;
use App\Modules\Gestions\Resources\CreanceResource;
use App\Modules\ResourceHumaine\Models\ClientHydrocarbure;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreanceController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'client',
        'affectationPistolet.pistolet.pompe.station',
        'createdBy',
        'updatedBy',
    ];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $creances = Creance::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('affectationPistolet.pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->orderBy('date_creance', 'desc')
            ->get();

        return $this->successResponse(
            CreanceResource::collection($creances),
            "Liste des creances chargee avec succes."
        );
    }

    public function show(Request $request, Creance $creance, UserStationScopeService $stationScopeService)
    {
        try {
            $creance = $this->resolveScopedCreance($request, $creance->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        return $this->successResponse(
            new CreanceResource($creance),
            "Creance chargee avec succes."
        );
    }

    public function store(CreanceRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();

        $affectation = $this->resolveAccessibleAffectationPistolet($data['affectation_pistolet_id'], $scope);
        if (! $affectation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data['created_by'] = Auth::id();
        try {
            $data['montant'] = $this->calculateMontant(
                (int) $data['total_litre'],
                $affectation,
                $data['client_id'] ?? null
            );
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $creance = Creance::create($data)->load($this->relations);

        logActivity("Creation d'une creance", $creance->toArray(), $creance);

        return $this->successResponse(
            new CreanceResource($creance),
            "Creance creee avec succes."
        );
    }

    public function update(CreanceRequest $request, Creance $creance, UserStationScopeService $stationScopeService)
    {
        try {
            $creance = $this->resolveScopedCreance($request, $creance->id, $stationScopeService);
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();

        $affectationId = $data['affectation_pistolet_id'] ?? $creance->affectation_pistolet_id;
        $affectation = $this->resolveAccessibleAffectationPistolet($affectationId, $scope);
        if (! $affectation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data['updated_by'] = Auth::id();
        $totalLitre = array_key_exists('total_litre', $data) ? (int) $data['total_litre'] : (int) $creance->total_litre;
        $clientId = array_key_exists('client_id', $data) ? $data['client_id'] : $creance->client_id;

        try {
            $data['montant'] = $this->calculateMontant($totalLitre, $affectation, $clientId);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $oldCreance = $creance->replicate()->fill($creance->getAttributes());

        $creance->update($data);
        $creance->load($this->relations);

        logActivity("Mise a jour d'une creance", [
            'oldCreance' => $oldCreance->toArray(),
            'newCreance' => $creance->toArray(),
        ], $creance);

        return $this->successResponse(
            new CreanceResource($creance),
            "Creance mise a jour avec succes."
        );
    }

    public function destroy(Request $request, Creance $creance, UserStationScopeService $stationScopeService)
    {
        try {
            $creance = $this->resolveScopedCreance($request, $creance->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        logActivity("Suppression d'une creance", $creance->toArray(), $creance);

        $creance->delete();

        return $this->noContentSuccessResponse("Creance supprimee avec succes.");
    }

    private function resolveScopedCreance(Request $request, int $creanceId, UserStationScopeService $stationScopeService): Creance
    {
        $scope = $stationScopeService->resolve($request->user());

        return Creance::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('affectationPistolet.pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->findOrFail($creanceId);
    }

    private function resolveAccessibleAffectationPistolet(int $affectationId, array $scope): ?AffectationPistolet
    {
        return AffectationPistolet::with(['pistolet.hydrocarbure', 'pistolet.pompe'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->find($affectationId);
    }

    private function calculateMontant(int $totalLitre, AffectationPistolet $affectation, ?int $clientId = null): float
    {
        if ($clientId) {
            $hydrocarbureId = $affectation->pistolet?->hydrocarbure_id;

            if (! $hydrocarbureId) {
                throw new AuthorizationException("Hydrocarbure du pistolet introuvable.");
            }

            $clientHydrocarbure = ClientHydrocarbure::where('client_id', $clientId)
                ->where('hydrocarbure_id', $hydrocarbureId)
                ->where('is_active', true)
                ->first();

            if (! $clientHydrocarbure) {
                throw new AuthorizationException("Ce client n'est pas autorise pour cet hydrocarbure.");
            }

            $prix = (float) ($clientHydrocarbure->prix ?? 0);

            return $totalLitre * $prix;
        }

        $prixVenteJour = (float) ($affectation->prix_vente_jour ?? 0);

        return $totalLitre * $prixVenteJour;
    }
}
