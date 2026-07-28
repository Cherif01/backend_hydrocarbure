<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\Pompe;
use App\Modules\Gestions\Requests\PompeRequest;
use App\Modules\Gestions\Resources\PompeResource;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PompeController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $scope = $this->stationScope($request);
        $pompes = Pompe::with(['station', 'createdBy', 'updatedBy'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            PompeResource::collection($pompes),
            'Liste des pompes chargee avec succes.'
        );
    }

    public function show(Request $request, Pompe $pompe)
    {
        $pompe = $this->resolveAccessiblePompe($pompe->id, $this->stationScope($request));

        return $this->successResponse(
            new PompeResource($pompe),
            'Pompe chargee avec succes.'
        );
    }

    public function store(PompeRequest $request)
    {
        $scope = $this->stationScope($request);
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if ($scope['is_station_scoped']) {
            $data['station_id'] = $scope['station_id'];
        }

        if (empty($data['reference'])) {
            $data['reference'] = $this->generateUniqueReference();
        }

        $pompe = Pompe::create($data)->load(['station', 'createdBy', 'updatedBy']);

        logActivity("Creation d'une pompe", $pompe->toArray(), $pompe);

        return $this->successResponse(
            new PompeResource($pompe),
            'Pompe creee avec succes.'
        );
    }

    public function update(PompeRequest $request, Pompe $pompe)
    {
        $scope = $this->stationScope($request);
        $pompe = $this->resolveAccessiblePompe($pompe->id, $scope);
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if ($scope['is_station_scoped']) {
            $data['station_id'] = $scope['station_id'];
        }

        if (empty($data['reference'])) {
            unset($data['reference']);
        }

        $oldPompe = $pompe->replicate()->fill($pompe->getAttributes());

        $pompe->update($data);
        $pompe->load(['station', 'createdBy', 'updatedBy']);

        logActivity("Mise a jour d'une pompe", [
            'oldPompe' => $oldPompe->toArray(),
            'newPompe' => $pompe->toArray(),
        ], $pompe);

        return $this->successResponse(
            new PompeResource($pompe),
            'Pompe mise a jour avec succes.'
        );
    }

    private function stationScope(Request $request): array
    {
        return $request->attributes->get('station_scope');
    }

    private function resolveAccessiblePompe(int $pompeId, array $scope): Pompe
    {
        return Pompe::with(['station', 'createdBy', 'updatedBy'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($pompeId);
    }

    private function generateUniqueReference(): string
    {
        $maxSuffix = Pompe::pluck('reference')->reduce(function (int $max, string $reference): int {
            if (preg_match('/^POM(\d+)$/', $reference, $matches) === 1) {
                return max($max, (int) $matches[1]);
            }

            return $max;
        }, 0);

        $nextSuffix = $maxSuffix + 1;

        do {
            $reference = 'POM'.str_pad((string) $nextSuffix, 2, '0', STR_PAD_LEFT);
            $nextSuffix++;
        } while (Pompe::where('reference', $reference)->exists());

        return $reference;
    }
}
