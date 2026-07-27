<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\AffectationStation;
use App\Modules\Gestions\Requests\AffectationStationRequest;
use App\Modules\Gestions\Resources\AffectationStationResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;

class AffectationStationController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $affectations = AffectationStation::with(['station', 'user', 'createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            AffectationStationResource::collection($affectations),
            "Liste des affectations de stations chargee avec succes."
        );
    }

    public function show(AffectationStation $affectation_station)
    {
        return $this->successResponse(
            new AffectationStationResource(
                $affectation_station->load(['station', 'user', 'createdBy', 'updatedBy'])
            ),
            "Affectation de station chargee avec succes."
        );
    }

    public function store(AffectationStationRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if (($data['is_active'] ?? true) && $this->userHasAnotherActiveAffectation($data['user_id'])) {
            return $this->errorResponse(
                "Cet utilisateur a deja une affectation de station active.",
                422
            );
        }

        $affectationStation = AffectationStation::create($data)->load(['station', 'user', 'createdBy', 'updatedBy']);

        logActivity("Creation d'une affectation de station", $affectationStation->toArray(), $affectationStation);

        return $this->successResponse(
            new AffectationStationResource($affectationStation),
            "Affectation de station creee avec succes."
        );
    }

    public function update(AffectationStationRequest $request, AffectationStation $affectation_station)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if (($data['is_active'] ?? $affectation_station->is_active)
            && $this->userHasAnotherActiveAffectation($data['user_id'], $affectation_station->id)
        ) {
            return $this->errorResponse(
                "Cet utilisateur a deja une affectation de station active.",
                422
            );
        }

        $oldAffectation = $affectation_station->replicate()->fill($affectation_station->getAttributes());

        $affectation_station->update($data);
        $affectation_station->load(['station', 'user', 'createdBy', 'updatedBy']);

        logActivity("Mise a jour d'une affectation de station", [
            'oldAffectationStation' => $oldAffectation->toArray(),
            'newAffectationStation' => $affectation_station->toArray(),
        ], $affectation_station);

        return $this->successResponse(
            new AffectationStationResource($affectation_station),
            "Affectation de station mise a jour avec succes."
        );
    }

    public function switchStatus(AffectationStation $affectation_station)
    {
        $nextStatus = !$affectation_station->is_active;

        if ($nextStatus && $this->userHasAnotherActiveAffectation($affectation_station->user_id, $affectation_station->id)) {
            return $this->errorResponse(
                "Cet utilisateur a deja une affectation de station active.",
                422
            );
        }

        $affectation_station->is_active = $nextStatus;
        $affectation_station->updated_by = Auth::id();
        $affectation_station->save();
        $affectation_station->load(['station', 'user', 'createdBy', 'updatedBy']);

        logActivity("Changement de statut d'une affectation de station", $affectation_station->toArray(), $affectation_station);

        return $this->successResponse(
            new AffectationStationResource($affectation_station),
            "Statut de l'affectation de station change avec succes."
        );
    }

    public function destroy(AffectationStation $affectation_station)
    {
        logActivity("Suppression d'une affectation de station", $affectation_station->toArray(), $affectation_station);

        $affectation_station->delete();

        return $this->noContentSuccessResponse("Affectation de station supprimee avec succes.");
    }

    private function userHasAnotherActiveAffectation(int $userId, ?int $ignoreId = null): bool
    {
        return AffectationStation::where('user_id', $userId)
            ->where('is_active', true)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }
}
