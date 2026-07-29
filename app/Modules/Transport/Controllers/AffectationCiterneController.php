<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\AffectationCiterne;
use App\Modules\Transport\Requests\AffectationCiterneRequest;
use App\Modules\Transport\Resources\AffectationCiterneResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;

class AffectationCiterneController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'employee',
        'citerne',
        'depenses',
        'createdBy',
        'updatedBy',
    ];

    public function index()
    {
        $affectations = AffectationCiterne::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            AffectationCiterneResource::collection($affectations),
            "Liste des affectations de citerne chargee avec succes."
        );
    }

    public function show(AffectationCiterne $affectation_citerne)
    {
        $affectation_citerne->load($this->relations);

        return $this->successResponse(
            new AffectationCiterneResource($affectation_citerne),
            "Affectation de citerne chargee avec succes."
        );
    }

    public function store(AffectationCiterneRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $status = $data['status'] ?? 'en_cours';

        if ($status === 'en_cours' && $this->employeeHasActiveAffectation((int) $data['employee_id'])) {
            return $this->errorResponse(
                "Ce chauffeur a deja une affectation de citerne en cours.",
                422
            );
        }

        if ($status === 'en_cours' && $this->citerneHasActiveAffectation((int) $data['citerne_id'])) {
            return $this->errorResponse(
                "Cette citerne a deja une affectation en cours.",
                422
            );
        }

        $affectation = AffectationCiterne::create($data)->load($this->relations);

        logActivity("Creation d'une affectation de citerne", $affectation->toArray(), $affectation);

        return $this->successResponse(
            new AffectationCiterneResource($affectation),
            "Affectation de citerne creee avec succes."
        );
    }

    public function update(AffectationCiterneRequest $request, AffectationCiterne $affectation_citerne)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $nextStatus = $data['status'] ?? $affectation_citerne->status;
        $nextEmployeeId = (int) ($data['employee_id'] ?? $affectation_citerne->employee_id);
        $nextCiterneId = (int) ($data['citerne_id'] ?? $affectation_citerne->citerne_id);

        if ($nextStatus === 'en_cours'
            && $this->employeeHasActiveAffectation($nextEmployeeId, $affectation_citerne->id)
        ) {
            return $this->errorResponse(
                "Ce chauffeur a deja une affectation de citerne en cours.",
                422
            );
        }

        if ($nextStatus === 'en_cours'
            && $this->citerneHasActiveAffectation($nextCiterneId, $affectation_citerne->id)
        ) {
            return $this->errorResponse(
                "Cette citerne a deja une affectation en cours.",
                422
            );
        }

        $oldAffectation = $affectation_citerne->replicate()->fill($affectation_citerne->getAttributes());

        $affectation_citerne->update($data);
        $affectation_citerne->load($this->relations);

        logActivity("Mise a jour d'une affectation de citerne", [
            'oldAffectationCiterne' => $oldAffectation->toArray(),
            'newAffectationCiterne' => $affectation_citerne->toArray(),
        ], $affectation_citerne);

        return $this->successResponse(
            new AffectationCiterneResource($affectation_citerne),
            "Affectation de citerne mise a jour avec succes."
        );
    }

    public function destroy(AffectationCiterne $affectation_citerne)
    {
        $affectation_citerne->load($this->relations);

        logActivity("Suppression d'une affectation de citerne", $affectation_citerne->toArray(), $affectation_citerne);

        $affectation_citerne->delete();

        return $this->noContentSuccessResponse("Affectation de citerne supprimee avec succes.");
    }

    private function employeeHasActiveAffectation(int $employeeId, ?int $ignoreId = null): bool
    {
        return AffectationCiterne::where('employee_id', $employeeId)
            ->where('status', 'en_cours')
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }

    private function citerneHasActiveAffectation(int $citerneId, ?int $ignoreId = null): bool
    {
        return AffectationCiterne::where('citerne_id', $citerneId)
            ->where('status', 'en_cours')
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }
}

