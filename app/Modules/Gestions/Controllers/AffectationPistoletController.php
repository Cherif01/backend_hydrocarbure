<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\AffectationPistolet;
use App\Modules\Gestions\Models\Pistolet;
use App\Modules\Gestions\Requests\AffectationPistoletRequest;
use App\Modules\Gestions\Resources\AffectationPistoletResource;
use App\Modules\ResourceHumaine\Models\Employee;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffectationPistoletController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'employee.post',
        'employee.station',
        'pistolet.hydrocarbure',
        'pistolet.pompe.station',
        'creances.paiementsCreances',
        'createdBy',
        'updatedBy',
    ];

    private const AUDIT_FIELDS = [
        'employee_id',
        'pistolet_id',
        'index_ouverture',
        'index_fermeture',
        'litre_vendu',
        'prix_vente_jour',
        'litre_retouner',
        'montant_attentu',
        'montant_recu',
        'commentaire',
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

        $affectations = AffectationPistolet::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            AffectationPistoletResource::collection($affectations),
            "Liste des affectations pistolets chargee avec succes."
        );
    }

    public function show(Request $request, AffectationPistolet $affectation_pistolet, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $affectation = $this->resolveAccessibleAffectationPistolet($affectation_pistolet->id, $scope);
        if (! $affectation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        return $this->successResponse(
            new AffectationPistoletResource($affectation),
            "Affectation pistolet chargee avec succes."
        );
    }

    public function store(AffectationPistoletRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();

        $employee = $this->resolveAccessibleEmployee((int) $data['employee_id'], $scope);
        if (! $employee) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $pistolet = $this->resolveAccessiblePistolet((int) $data['pistolet_id'], $scope);
        if (! $pistolet) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        if ($this->hasActiveAffectationForEmployee((int) $employee->id)) {
            return $this->errorResponse("Cet employe a deja une affectation active.", 422);
        }

        if ($this->hasActiveAffectationForPistolet((int) $pistolet->id)) {
            return $this->errorResponse("Ce pistolet a deja une affectation active.", 422);
        }

        $data['created_by'] = Auth::id();
        $data = $this->applyComputedFields($data, $pistolet);

        $affectation = AffectationPistolet::create($data)->load($this->relations);

        logActivity("Creation d'une affectation pistolet", $affectation->only(self::AUDIT_FIELDS), $affectation);

        return $this->successResponse(
            new AffectationPistoletResource($affectation),
            "Affectation pistolet creee avec succes."
        );
    }

    public function update(AffectationPistoletRequest $request, AffectationPistolet $affectation_pistolet, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $affectation = $this->resolveAccessibleAffectationPistolet($affectation_pistolet->id, $scope);
        if (! $affectation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $employeeId = (int) ($data['employee_id'] ?? $affectation->employee_id);
        $pistoletId = (int) ($data['pistolet_id'] ?? $affectation->pistolet_id);
        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : (bool) $affectation->is_active;

        $employee = $this->resolveAccessibleEmployee($employeeId, $scope);
        if (! $employee) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $pistolet = $this->resolveAccessiblePistolet($pistoletId, $scope);
        if (! $pistolet) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        if ($isActive && $this->hasActiveAffectationForEmployee($employeeId, (int) $affectation->id)) {
            return $this->errorResponse("Cet employe a deja une affectation active.", 422);
        }

        if ($isActive && $this->hasActiveAffectationForPistolet($pistoletId, (int) $affectation->id)) {
            return $this->errorResponse("Ce pistolet a deja une affectation active.", 422);
        }

        $oldAffectation = $affectation->only(self::AUDIT_FIELDS);

        $data = $this->applyComputedFields($data, $pistolet, $affectation);

        $affectation->update($data);
        $affectation->load($this->relations);

        logActivity("Mise a jour d'une affectation pistolet", [
            'oldAffectationPistolet' => $oldAffectation,
            'newAffectationPistolet' => $affectation->only(self::AUDIT_FIELDS),
        ], $affectation);

        return $this->successResponse(
            new AffectationPistoletResource($affectation),
            "Affectation pistolet mise a jour avec succes."
        );
    }

    public function destroy(Request $request, AffectationPistolet $affectation_pistolet, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $affectation = $this->resolveAccessibleAffectationPistolet($affectation_pistolet->id, $scope);
        if (! $affectation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        logActivity("Suppression d'une affectation pistolet", $affectation->only(self::AUDIT_FIELDS), $affectation);

        $affectation->delete();

        return $this->noContentSuccessResponse("Affectation pistolet supprimee avec succes.");
    }

    private function resolveAccessibleAffectationPistolet(int $affectationId, array $scope): ?AffectationPistolet
    {
        return AffectationPistolet::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('pistolet.pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->find($affectationId);
    }

    private function resolveAccessibleEmployee(int $employeeId, array $scope): ?Employee
    {
        return Employee::with(['post', 'station'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->find($employeeId);
    }

    private function resolveAccessiblePistolet(int $pistoletId, array $scope): ?Pistolet
    {
        return Pistolet::with(['hydrocarbure', 'pompe.station'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('pompe', function ($pompeQuery) use ($scope) {
                    $pompeQuery->where('station_id', $scope['station_id']);
                });
            })
            ->find($pistoletId);
    }

    private function hasActiveAffectationForEmployee(int $employeeId, ?int $excludeId = null): bool
    {
        return AffectationPistolet::query()
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->exists();
    }

    private function hasActiveAffectationForPistolet(int $pistoletId, ?int $excludeId = null): bool
    {
        return AffectationPistolet::query()
            ->where('pistolet_id', $pistoletId)
            ->where('is_active', true)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->exists();
    }

    private function applyComputedFields(array $data, Pistolet $pistolet, ?AffectationPistolet $current = null): array
    {
        $indexOuverture = (float) ($data['index_ouverture'] ?? $current?->index_ouverture ?? 0);
        $indexFermeture = (float) ($data['index_fermeture'] ?? $current?->index_fermeture ?? 0);
        $litreRetourner = (float) ($data['litre_retouner'] ?? $current?->litre_retouner ?? 0);

        $prixVenteJour = (float) ($pistolet->hydrocarbure?->prix_vente ?? 0);

        $litreVendu = $indexFermeture > 0 ? max(0, $indexFermeture - $indexOuverture) : 0;
        $montantAttentu = max(0, ($litreVendu - $litreRetourner) * $prixVenteJour);

        $data['prix_vente_jour'] = $prixVenteJour;
        $data['litre_vendu'] = $litreVendu;
        $data['montant_attentu'] = $montantAttentu;

        return $data;
    }
}
