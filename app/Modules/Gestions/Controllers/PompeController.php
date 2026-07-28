<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\Pompe;
use App\Modules\Gestions\Requests\PompeRequest;
use App\Modules\Gestions\Resources\PompeResource;
use App\Traits\ApiResponses;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PompeController extends Controller
{
    use ApiResponses;

    private const AUDIT_FIELDS = [
        'reference',
        'station_id',
        'libelle',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

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

        $hasAutomaticReference = $this->referenceIsMissingOrEmpty($data);
        $pompe = $this->createWithReferenceRetry($data, $hasAutomaticReference)
            ->load(['station', 'createdBy', 'updatedBy']);

        logActivity("Creation d'une pompe", $pompe->only(self::AUDIT_FIELDS), $pompe);

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

        if ($this->referenceIsMissingOrEmpty($data)) {
            unset($data['reference']);
        }

        $oldPompe = $pompe->only(self::AUDIT_FIELDS);

        $pompe->update($data);
        $pompe->load(['station', 'createdBy', 'updatedBy']);

        logActivity("Mise a jour d'une pompe", [
            'oldPompe' => $oldPompe,
            'newPompe' => $pompe->only(self::AUDIT_FIELDS),
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

    private function referenceIsMissingOrEmpty(array $data): bool
    {
        return ! array_key_exists('reference', $data)
            || $data['reference'] === null
            || $data['reference'] === '';
    }

    private function resolveAccessiblePompe(int $pompeId, array $scope): Pompe
    {
        return Pompe::with(['station', 'createdBy', 'updatedBy'])
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($pompeId);
    }

    private function createWithReferenceRetry(array $data, bool $hasAutomaticReference): Pompe
    {
        $maxAttempts = $hasAutomaticReference ? 3 : 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($hasAutomaticReference) {
                $data['reference'] = $this->generateUniqueReference();
            }

            try {
                return Pompe::create($data);
            } catch (QueryException $exception) {
                $canRetry = $hasAutomaticReference
                    && $attempt < $maxAttempts
                    && $this->isReferenceCollision($exception);

                if (! $canRetry) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('La creation de la pompe a echoue.');
    }

    private function isReferenceCollision(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? (string) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && (str_contains($message, 'pompes.reference')
                || str_contains($message, 'pompes_reference_unique'));
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
