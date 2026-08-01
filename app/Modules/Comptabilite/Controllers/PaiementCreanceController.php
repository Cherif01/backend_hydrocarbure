<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\PaiementCreance;
use App\Modules\Comptabilite\Requests\PaiementCreanceRequest;
use App\Modules\Comptabilite\Resources\PaiementCreanceResource;
use App\Modules\Gestions\Models\Creance;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use App\Traits\Helper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaiementCreanceController extends Controller
{
    use ApiResponses, Helper;

    private array $relations = [
        'client',
        'creance.client',
        'creance.affectationPistolet.pistolet.pompe.station',
        'createdBy',
        'updatedBy',
    ];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        if ($this->hasComptabiliteModule($user)) {
            $scope = [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        } else {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $paiements = PaiementCreance::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('creance.affectationPistolet.pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            PaiementCreanceResource::collection($paiements),
            "Liste des paiements de creance chargee avec succes."
        );
    }

    public function show(Request $request, PaiementCreance $paiement_creance, UserStationScopeService $stationScopeService)
    {
        try {
            $paiement = $this->resolveScopedPaiement($request, $paiement_creance->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        return $this->successResponse(
            new PaiementCreanceResource($paiement),
            "Paiement charge avec succes."
        );
    }

    public function store(PaiementCreanceRequest $request, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        if ($this->hasComptabiliteModule($user)) {
            $scope = [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        } else {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['reference'] = $data['reference'] ?? $this->generateUniqueReference();

        $creance = $this->resolveAccessibleCreance((int) $data['creance_id'], $scope);
        if (! $creance) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        if (! $creance->client_id) {
            return $this->errorResponse("La creance n'est pas associee a un client.", 422);
        }

        if ((int) $creance->client_id !== (int) $data['client_id']) {
            return $this->errorResponse("Le client ne correspond pas a la creance.", 422);
        }

        $overpaymentError = $this->validateNoOverpayment($creance, (float) $data['montant']);
        if ($overpaymentError) {
            return $this->errorResponse($overpaymentError, 422);
        }

        $solde_client = $this->soldeClient($creance->client_id) + $data['montant'];
        if ($solde_client < 0) {
            return $this->errorResponse("Solde client insuffisant.", 400);
        }

        $paiement = PaiementCreance::create($data)->load($this->relations);

        logActivity("Creation d'un paiement de creance", $paiement->toArray(), $paiement);

        return $this->successResponse(
            new PaiementCreanceResource($paiement),
            "Paiement cree avec succes."
        );
    }

    public function update(PaiementCreanceRequest $request, PaiementCreance $paiement_creance, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        if ($this->hasComptabiliteModule($user)) {
            $scope = [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        } else {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $paiement = $this->resolveAccessiblePaiement($paiement_creance->id, $scope);
        if (! $paiement) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if (empty($data['reference'])) {
            unset($data['reference']);
        }

        $creanceId = (int) ($data['creance_id'] ?? $paiement->creance_id);
        $creance = $this->resolveAccessibleCreance($creanceId, $scope);
        if (! $creance) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $clientId = (int) ($data['client_id'] ?? $paiement->client_id);

        if (! $creance->client_id) {
            return $this->errorResponse("La creance n'est pas associee a un client.", 422);
        }

        if ((int) $creance->client_id !== (int) $clientId) {
            return $this->errorResponse("Le client ne correspond pas a la creance.", 422);
        }

        $newMontant = (float) ($data['montant'] ?? $paiement->montant);
        $overpaymentError = $this->validateNoOverpayment($creance, $newMontant, (int) $paiement->id);
        if ($overpaymentError) {
            return $this->errorResponse($overpaymentError, 422);
        }

        $oldPaiement = $paiement->replicate()->fill($paiement->getAttributes());

        $solde_client = $this->soldeClient($creance->client_id) + $newMontant - $paiement->montant;
        if ($solde_client < 0) {
            return $this->errorResponse("Solde client insuffisant.", 400);
        }

        $paiement->update($data);
        $paiement->load($this->relations);

        logActivity("Mise a jour d'un paiement de creance", [
            'oldPaiement' => $oldPaiement->toArray(),
            'newPaiement' => $paiement->toArray(),
        ], $paiement);

        return $this->successResponse(
            new PaiementCreanceResource($paiement),
            "Paiement mis a jour avec succes."
        );
    }

    public function destroy(Request $request, PaiementCreance $paiement_creance, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        if ($this->hasComptabiliteModule($user)) {
            $scope = [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        } else {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $paiement = $this->resolveAccessiblePaiement($paiement_creance->id, $scope);
        if (! $paiement) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        logActivity("Suppression d'un paiement de creance", $paiement->toArray(), $paiement);

        $paiement->delete();

        return $this->noContentSuccessResponse("Paiement supprime avec succes.");
    }

    private function resolveScopedPaiement(Request $request, int $paiementId, UserStationScopeService $stationScopeService): PaiementCreance
    {
        $user = $request->user();

        if ($this->hasComptabiliteModule($user)) {
            $scope = [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        } else {
            $scope = $stationScopeService->resolve($user);
        }

        return PaiementCreance::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('creance.affectationPistolet.pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->findOrFail($paiementId);
    }

    private function resolveAccessiblePaiement(int $paiementId, array $scope): ?PaiementCreance
    {
        return PaiementCreance::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('creance.affectationPistolet.pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->find($paiementId);
    }

    private function resolveAccessibleCreance(int $creanceId, array $scope): ?Creance
    {
        return Creance::with(['affectationPistolet.pistolet.pompe'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('affectationPistolet.pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->find($creanceId);
    }

    private function hasComptabiliteModule($user): bool
    {
        return $user?->userModules()
            ->where('is_active', true)
            ->whereHas('module', function ($query) {
                $query->where('name', 'comptabilite')
                    ->where('is_active', true);
            })
            ->exists() ?? false;
    }

    private function generateUniqueReference(): string
    {
        do {
            $reference = 'CLPAI-' . Str::upper(Str::random(6));
        } while (PaiementCreance::where('reference', $reference)->exists());

        return $reference;
    }

    private function validateNoOverpayment(Creance $creance, float $newMontant, ?int $excludePaiementId = null): ?string
    {
        $montantCreance = $creance->montant !== null ? (float) $creance->montant : 0.0;
        $paidSoFar = $this->getPaidAmountForCreance((int) $creance->id, $excludePaiementId);
        $totalAfter = $paidSoFar + $newMontant;

        if ($totalAfter > ($montantCreance + 0.00001)) {
            $remaining = max(0, $montantCreance - $paidSoFar);

            return "Surpaiement non autorise. Montant restant de la creance : " . number_format($remaining, 2, '.', '') . ".";
        }

        return null;
    }

    private function getPaidAmountForCreance(int $creanceId, ?int $excludePaiementId = null): float
    {
        return (float) PaiementCreance::query()
            ->where('creance_id', $creanceId)
            ->when($excludePaiementId, function ($query) use ($excludePaiementId) {
                $query->where('id', '!=', $excludePaiementId);
            })
            ->sum('montant');
    }
}
