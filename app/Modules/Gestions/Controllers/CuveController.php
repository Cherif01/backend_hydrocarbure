<?php

namespace App\Modules\Gestions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gestions\Models\Cuve;
use App\Modules\Gestions\Requests\CuveRequest;
use App\Modules\Gestions\Resources\CuveResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CuveController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'station',
        'hydrocarbure',
        'createdBy',
        'updatedBy',
    ];

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $cuves = Cuve::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            CuveResource::collection($cuves),
            "Liste des cuves chargee avec succes."
        );
    }

    public function show(Request $request, Cuve $cuve, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $cuve = $this->resolveAccessibleCuve((int) $cuve->id, $scope);

        return $this->successResponse(
            new CuveResource($cuve),
            "Cuve chargee avec succes."
        );
    }

    public function store(CuveRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['reference'] = $data['reference'] ?? $this->generateUniqueReference();

        if ($scope['is_station_scoped']) {
            $data['station_id'] = $scope['station_id'];
        }

        if (! $this->isAdmin($request->user()) && empty($data['station_id'])) {
            return $this->errorResponse("La station est obligatoire.", 422);
        }

        $hasAutomaticReference = $this->referenceIsMissingOrEmpty($data);
        $cuve = $this->createWithReferenceRetry($data, $hasAutomaticReference)->load($this->relations);

        logActivity("Creation d'une cuve", $cuve->toArray(), $cuve);

        return $this->successResponse(
            new CuveResource($cuve),
            "Cuve creee avec succes."
        );
    }

    public function update(CuveRequest $request, Cuve $cuve, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $cuve = $this->resolveAccessibleCuve((int) $cuve->id, $scope);

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if ($scope['is_station_scoped']) {
            $data['station_id'] = $scope['station_id'];
        }

        if ($this->referenceIsMissingOrEmpty($data)) {
            unset($data['reference']);
        }

        $oldCuve = $cuve->replicate()->fill($cuve->getAttributes());

        $cuve->update($data);
        $cuve->load($this->relations);

        logActivity("Mise a jour d'une cuve", [
            'oldCuve' => $oldCuve->toArray(),
            'newCuve' => $cuve->toArray(),
        ], $cuve);

        return $this->successResponse(
            new CuveResource($cuve),
            "Cuve mise a jour avec succes."
        );
    }

    public function destroy(Request $request, Cuve $cuve, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $cuve = $this->resolveAccessibleCuve((int) $cuve->id, $scope);

        logActivity("Suppression d'une cuve", $cuve->toArray(), $cuve);

        $cuve->delete();

        return $this->noContentSuccessResponse("Cuve supprimee avec succes.");
    }

    private function resolveScope(Request $request, UserStationScopeService $stationScopeService): array
    {
        $user = $request->user();

        if ($this->isAdmin($user)) {
            return [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        }

        if (! $this->hasActiveModule($user, 'gerant_station')) {
            throw new AuthorizationException("Vous n'avez pas la permission d'effectuer cette operation.");
        }

        return $stationScopeService->resolve($user);
    }

    private function resolveAccessibleCuve(int $cuveId, array $scope): Cuve
    {
        return Cuve::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($cuveId);
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

    private function referenceIsMissingOrEmpty(array $data): bool
    {
        return ! array_key_exists('reference', $data)
            || $data['reference'] === null
            || $data['reference'] === '';
    }

    private function createWithReferenceRetry(array $data, bool $hasAutomaticReference): Cuve
    {
        $maxAttempts = $hasAutomaticReference ? 3 : 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($hasAutomaticReference) {
                $data['reference'] = $this->generateUniqueReference();
            }

            try {
                return Cuve::create($data);
            } catch (QueryException $exception) {
                $canRetry = $hasAutomaticReference
                    && $attempt < $maxAttempts
                    && $this->isReferenceCollision($exception);

                if (! $canRetry) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException("La creation de la cuve a echoue.");
    }

    private function isReferenceCollision(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? (string) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && (str_contains($message, 'cuves.reference')
                || str_contains($message, 'cuves_reference_unique'));
    }

    private function generateUniqueReference(): string
    {
        do {
            $reference = 'CUV-' . Str::upper(Str::random(6));
        } while (Cuve::where('reference', $reference)->exists());

        return $reference;
    }
}
