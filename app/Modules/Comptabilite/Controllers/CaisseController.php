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
use Illuminate\Support\Facades\DB;

class CaisseController extends Controller
{
    use ApiResponses;

    private const VERSEMENT_DEBIT_STATUSES = ['recu', 'confirmer'];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $caisses = Caisse::with(['station', 'createdBy', 'updatedBy'])
            ->withSum(['operations as operations_entree_sum' => function ($query) {
                $query->whereRelation('typeOperation', 'nature', true);
            }], 'montant')
            ->withSum(['operations as operations_sortie_sum' => function ($query) {
                $query->whereRelation('typeOperation', 'nature', false);
            }], 'montant')
            ->withSum(['versements as versements_sortie_sum' => function ($query) {
                $query->whereIn('status', self::VERSEMENT_DEBIT_STATUSES);
            }], 'montant')
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $this->applyStationCashSums($caisses);

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

        $this->applyStationCashSums(collect([$caisse]));

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
        $caisse->loadSum(['operations as operations_entree_sum' => function ($query) {
            $query->whereRelation('typeOperation', 'nature', true);
        }], 'montant');
        $caisse->loadSum(['operations as operations_sortie_sum' => function ($query) {
            $query->whereRelation('typeOperation', 'nature', false);
        }], 'montant');
        $caisse->loadSum(['versements as versements_sortie_sum' => function ($query) {
            $query->whereIn('status', self::VERSEMENT_DEBIT_STATUSES);
        }], 'montant');

        $this->applyStationCashSums(collect([$caisse]));

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
        $caisse->loadSum(['operations as operations_entree_sum' => function ($query) {
            $query->whereRelation('typeOperation', 'nature', true);
        }], 'montant');
        $caisse->loadSum(['operations as operations_sortie_sum' => function ($query) {
            $query->whereRelation('typeOperation', 'nature', false);
        }], 'montant');
        $caisse->loadSum(['versements as versements_sortie_sum' => function ($query) {
            $query->whereIn('status', self::VERSEMENT_DEBIT_STATUSES);
        }], 'montant');

        $this->applyStationCashSums(collect([$caisse]));

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
            ->withSum(['operations as operations_entree_sum' => function ($query) {
                $query->whereRelation('typeOperation', 'nature', true);
            }], 'montant')
            ->withSum(['operations as operations_sortie_sum' => function ($query) {
                $query->whereRelation('typeOperation', 'nature', false);
            }], 'montant')
            ->withSum(['versements as versements_sortie_sum' => function ($query) {
                $query->whereIn('status', self::VERSEMENT_DEBIT_STATUSES);
            }], 'montant')
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($caisseId);
    }

    private function applyStationCashSums($caisses): void
    {
        $collection = is_iterable($caisses) ? collect($caisses) : collect();

        $stationIds = $collection->pluck('station_id')->filter()->unique()->values();
        if ($stationIds->isEmpty()) {
            return;
        }

        $primaryCaisseIds = Caisse::query()
            ->whereIn('station_id', $stationIds)
            ->selectRaw('station_id, MIN(id) as caisse_id')
            ->groupBy('station_id')
            ->pluck('caisse_id', 'station_id');

        $affectationsMontantRecu = DB::table('affectation_pistolets')
            ->join('pistolets', 'pistolets.id', '=', 'affectation_pistolets.pistolet_id')
            ->join('pompes', 'pompes.id', '=', 'pistolets.pompe_id')
            ->whereIn('pompes.station_id', $stationIds)
            ->where('affectation_pistolets.is_active', false)
            ->selectRaw('pompes.station_id as station_id, COALESCE(SUM(affectation_pistolets.montant_recu), 0) as montant_sum')
            ->groupBy('pompes.station_id')
            ->pluck('montant_sum', 'station_id');

        $paiementsCreances = DB::table('paiement_creances')
            ->join('creances', 'creances.id', '=', 'paiement_creances.creance_id')
            ->join('affectation_pistolets', 'affectation_pistolets.id', '=', 'creances.affectation_pistolet_id')
            ->join('pistolets', 'pistolets.id', '=', 'affectation_pistolets.pistolet_id')
            ->join('pompes', 'pompes.id', '=', 'pistolets.pompe_id')
            ->whereIn('pompes.station_id', $stationIds)
            ->whereNull('paiement_creances.deleted_at')
            ->selectRaw('pompes.station_id as station_id, COALESCE(SUM(paiement_creances.montant), 0) as montant_sum')
            ->groupBy('pompes.station_id')
            ->pluck('montant_sum', 'station_id');

        foreach ($collection as $caisse) {
            $stationId = $caisse->station_id;
            $isPrimary = (int) $caisse->id === (int) ($primaryCaisseIds[$stationId] ?? 0);

            $caisse->setAttribute('is_primary', $isPrimary);
            $caisse->setAttribute('affectations_montant_recu_sum', (float) ($affectationsMontantRecu[$stationId] ?? 0));
            $caisse->setAttribute('paiements_creances_sum', (float) ($paiementsCreances[$stationId] ?? 0));
        }
    }
}
