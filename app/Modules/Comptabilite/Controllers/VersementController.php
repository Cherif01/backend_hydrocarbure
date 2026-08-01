<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\Caisse;
use App\Modules\Comptabilite\Models\Versement;
use App\Modules\Comptabilite\Requests\VersementRequest;
use App\Modules\Comptabilite\Resources\VersementResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use App\Traits\Helper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VersementController extends Controller
{
    use ApiResponses, Helper;

    private const CAISSE_DEBIT_STATUSES = ['recu', 'confirmer'];

    private array $relations = [
        'compte',
        'caisse.station',
        'user',
        'createdBy',
        'updatedBy',
    ];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        $isAdmin = $this->isAdmin($user);
        $hasComptabilite = $this->hasActiveModule($user, 'comptabilite');
        $hasGerantStation = $this->hasActiveModule($user, 'gerant_station');

        $scope = ['is_station_scoped' => false, 'station_id' => null];

        if (! $isAdmin) {
            if (! $hasComptabilite && ! $hasGerantStation) {
                return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
            }

            if ($hasGerantStation) {
                try {
                    $scope = $stationScopeService->resolve($user);
                } catch (AuthorizationException $exception) {
                    return $this->errorResponse($exception->getMessage(), 403);
                }
            }
        }

        $versements = Versement::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where(function ($scopedQuery) use ($scope) {
                    $scopedQuery->whereHas('caisse', function ($caisseQuery) use ($scope) {
                        $caisseQuery->where('station_id', $scope['station_id']);
                    })->orWhere('user_id', Auth::id());
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            VersementResource::collection($versements),
            "Liste des versements chargee avec succes."
        );
    }

    public function show(Request $request, Versement $versement, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        $isAdmin = $this->isAdmin($user);
        $hasComptabilite = $this->hasActiveModule($user, 'comptabilite');
        $hasGerantStation = $this->hasActiveModule($user, 'gerant_station');

        if (! $isAdmin && ! $hasComptabilite && ! $hasGerantStation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $scope = ['is_station_scoped' => false, 'station_id' => null];

        if (! $isAdmin && $hasGerantStation) {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $versementScoped = $this->resolveAccessibleVersement((int) $versement->id, $scope);
        if (! $versementScoped) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        return $this->successResponse(
            new VersementResource($versementScoped),
            "Versement charge avec succes."
        );
    }

    public function switchStatus(Request $request, Versement $versement)
    {
        $user = $request->user();

        $isAdmin = $this->isAdmin($user);
        $hasComptabilite = $this->hasActiveModule($user, 'comptabilite');
        $hasGerantStation = $this->hasActiveModule($user, 'gerant_station');
        $isIntermediary = (int) $versement->user_id === (int) $user?->id;

        if (! $isAdmin && ! $hasComptabilite && ! $hasGerantStation && ! $isIntermediary) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        if ($hasGerantStation && ! $isAdmin && ! $isIntermediary) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['en_cours', 'rejeter', 'annuler', 'recu', 'confirmer'])],
        ]);

        $currentStatus = $versement->status;
        $nextStatus = $data['status'];

        if ($isIntermediary && ! $isAdmin && ! $hasComptabilite) {
            if ($nextStatus === 'confirmer') {
                return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
            }
        } else {
            $isAllowed =
                ($currentStatus === 'en_cours' && in_array($nextStatus, ['rejeter', 'annuler', 'recu', 'confirmer'], true))
                || ($currentStatus === 'rejeter' && $nextStatus === 'confirmer')
                || ($currentStatus === 'recu' && $nextStatus === 'confirmer');

            if (! $isAllowed) {
                return $this->errorResponse("Ce versement ne peut plus changer de statut.", 422);
            }
        }

        $oldVersement = $versement->replicate()->fill($versement->getAttributes());

        $isCurrentDebited = in_array($currentStatus, self::CAISSE_DEBIT_STATUSES, true);
        $isNextDebited = in_array($nextStatus, self::CAISSE_DEBIT_STATUSES, true);
        if (! $isCurrentDebited && $isNextDebited) {
            $solde = $this->soldeCaisseFromDb((int) $versement->caisse_id);
            $montant = (float) ($versement->montant ?? 0);
            if (($solde - $montant) < 0) {
                return $this->errorResponse("Solde de la caisse insuffisant.", 400);
            }
        }

        $versement->status = $nextStatus;
        if ($nextStatus === 'recu' && $versement->date_reception === null) {
            $versement->date_reception = now();
        }
        $versement->updated_by = Auth::id();
        $versement->save();
        $versement->load($this->relations);

        logActivity("Changement de statut d'un versement", [
            'oldVersement' => $oldVersement->toArray(),
            'newVersement' => $versement->toArray(),
        ], $versement);

        return $this->successResponse(
            new VersementResource($versement),
            "Statut du versement change avec succes."
        );
    }

    public function store(VersementRequest $request, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        $isAdmin = $this->isAdmin($user);
        $hasComptabilite = $this->hasActiveModule($user, 'comptabilite');
        $hasGerantStation = $this->hasActiveModule($user, 'gerant_station');

        if (! $isAdmin && ! $hasComptabilite && ! $hasGerantStation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $scope = ['is_station_scoped' => false, 'station_id' => null];
        if (! $isAdmin && $hasGerantStation) {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $type = $data['type'] ?? 'direct';
        if ($type === 'direct') {
            $data['user_id'] = null;
        }

        if (
            $scope['is_station_scoped']
            && ! Caisse::where('id', $data['caisse_id'])->where('station_id', $scope['station_id'])->exists()
        ) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $status = $data['status'] ?? 'en_cours';
        if (in_array($status, self::CAISSE_DEBIT_STATUSES, true)) {
            $solde = $this->soldeCaisseFromDb((int) $data['caisse_id']);
            $montant = (float) ($data['montant'] ?? 0);
            if (($solde - $montant) < 0) {
                return $this->errorResponse("Solde de la caisse insuffisant.", 400);
            }
        }

        if ($status === 'recu' && ! array_key_exists('date_reception', $data)) {
            $data['date_reception'] = now();
        }

        $versement = Versement::create($data)->load($this->relations);

        logActivity("Creation d'un versement", $versement->toArray(), $versement);

        return $this->successResponse(
            new VersementResource($versement),
            "Versement cree avec succes."
        );
    }

    public function update(VersementRequest $request, Versement $versement, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        $isAdmin = $this->isAdmin($user);
        $hasComptabilite = $this->hasActiveModule($user, 'comptabilite');
        $hasGerantStation = $this->hasActiveModule($user, 'gerant_station');

        if (! $isAdmin && ! $hasComptabilite && ! $hasGerantStation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $scope = ['is_station_scoped' => false, 'station_id' => null];
        if (! $isAdmin && $hasGerantStation) {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $versementScoped = $this->resolveAccessibleVersement((int) $versement->id, $scope);
        if (! $versementScoped) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $type = $data['type'] ?? $versementScoped->type;
        if ($type === 'direct') {
            $data['user_id'] = null;
        }

        $caisseId = (int) ($data['caisse_id'] ?? $versementScoped->caisse_id);
        if (
            $scope['is_station_scoped']
            && ! Caisse::where('id', $caisseId)->where('station_id', $scope['station_id'])->exists()
        ) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $oldCaisseId = $versementScoped->caisse_id !== null ? (int) $versementScoped->caisse_id : null;
        $newCaisseId = array_key_exists('caisse_id', $data) && $data['caisse_id'] !== null
            ? (int) $data['caisse_id']
            : $oldCaisseId;

        $oldStatus = $versementScoped->status;
        $newStatus = array_key_exists('status', $data) ? $data['status'] : $oldStatus;

        $oldMontant = (float) ($versementScoped->montant ?? 0);
        $newMontant = array_key_exists('montant', $data) ? (float) $data['montant'] : $oldMontant;

        $oldEffect = ($oldCaisseId !== null && in_array($oldStatus, self::CAISSE_DEBIT_STATUSES, true)) ? -$oldMontant : 0.0;
        $newEffect = ($newCaisseId !== null && in_array($newStatus, self::CAISSE_DEBIT_STATUSES, true)) ? -$newMontant : 0.0;

        if ($oldCaisseId !== null && $newCaisseId !== null && $oldCaisseId === $newCaisseId) {
            $soldeCurrent = $this->soldeCaisseFromDb($newCaisseId);
            $soldeAfter = $soldeCurrent - $oldEffect + $newEffect;
            if ($soldeAfter < 0) {
                return $this->errorResponse("Solde de la caisse insuffisant.", 400);
            }
        } else {
            if ($oldCaisseId !== null) {
                $soldeOldCurrent = $this->soldeCaisseFromDb($oldCaisseId);
                $soldeOldAfter = $soldeOldCurrent - $oldEffect;
                if ($soldeOldAfter < 0) {
                    return $this->errorResponse("Solde de la caisse insuffisant.", 400);
                }
            }

            if ($newCaisseId !== null) {
                $soldeNewCurrent = $this->soldeCaisseFromDb($newCaisseId);
                $soldeNewAfter = $soldeNewCurrent + $newEffect;
                if ($soldeNewAfter < 0) {
                    return $this->errorResponse("Solde de la caisse insuffisant.", 400);
                }
            }
        }

        if ($newStatus === 'recu' && $versementScoped->date_reception === null && ! array_key_exists('date_reception', $data)) {
            $data['date_reception'] = now();
        }

        $oldVersement = $versementScoped->replicate()->fill($versementScoped->getAttributes());

        $versementScoped->update($data);
        $versementScoped->load($this->relations);

        logActivity("Mise a jour d'un versement", [
            'oldVersement' => $oldVersement->toArray(),
            'newVersement' => $versementScoped->toArray(),
        ], $versementScoped);

        return $this->successResponse(
            new VersementResource($versementScoped),
            "Versement mis a jour avec succes."
        );
    }

    public function destroy(Request $request, Versement $versement, UserStationScopeService $stationScopeService)
    {
        $user = $request->user();

        $isAdmin = $this->isAdmin($user);
        $hasComptabilite = $this->hasActiveModule($user, 'comptabilite');
        $hasGerantStation = $this->hasActiveModule($user, 'gerant_station');

        if (! $isAdmin && ! $hasComptabilite && ! $hasGerantStation) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $scope = ['is_station_scoped' => false, 'station_id' => null];
        if (! $isAdmin && $hasGerantStation) {
            try {
                $scope = $stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }
        }

        $versementScoped = $this->resolveAccessibleVersement((int) $versement->id, $scope);
        if (! $versementScoped) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        logActivity("Suppression d'un versement", $versementScoped->toArray(), $versementScoped);

        $versementScoped->delete();

        return $this->noContentSuccessResponse("Versement supprime avec succes.");
    }

    private function resolveAccessibleVersement(int $versementId, array $scope): ?Versement
    {
        return Versement::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->whereHas('caisse', function ($caisseQuery) use ($scope) {
                    $caisseQuery->where('station_id', $scope['station_id']);
                });
            })
            ->find($versementId);
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
