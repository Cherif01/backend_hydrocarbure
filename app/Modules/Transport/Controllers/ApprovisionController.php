<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\Approvision;
use App\Modules\Transport\Requests\ApprovisionRequest;
use App\Modules\Transport\Resources\ApprovisionResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApprovisionController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'station',
        'affectationCiterne.employee',
        'affectationCiterne.citerne',
        'compartimentJauges.hydrocarbure',
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

        $approvisions = Approvision::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            ApprovisionResource::collection($approvisions),
            "Liste des approvisionnements chargee avec succes."
        );
    }

    public function show(Request $request, Approvision $approvision, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $approvision = $this->resolveAccessibleApprovision((int) $approvision->id, $scope);

        return $this->successResponse(
            new ApprovisionResource($approvision),
            "Approvisionnement charge avec succes."
        );
    }

    public function store(ApprovisionRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();
        $jauges = $data['appro_compartiment_jauges'] ?? [];
        unset($data['appro_compartiment_jauges']);

        $data['created_by'] = Auth::id();
        $data['reference'] = $data['reference'] ?? $this->generateUniqueReference();

        if ($scope['is_station_scoped']) {
            $data['station_id'] = $scope['station_id'];
        }

        if (! $data['station_id']) {
            return $this->errorResponse("La station est obligatoire.", 422);
        }

        $hasAutomaticReference = $this->referenceIsMissingOrEmpty($data);

        try {
            $approvision = DB::transaction(function () use ($data, $jauges, $hasAutomaticReference) {
                $approvision = $this->createWithReferenceRetry($data, $hasAutomaticReference);

                foreach ($jauges as $jauge) {
                    $jauge['created_by'] = Auth::id();
                    $approvision->compartimentJauges()->create($jauge);
                }

                return $approvision->load($this->relations);
            });
        } catch (QueryException $exception) {
            return $this->errorResponse("Erreur lors de la creation de l'approvisionnement.", 422);
        }

        logActivity("Creation d'un approvisionnement", $approvision->toArray(), $approvision);

        return $this->successResponse(
            new ApprovisionResource($approvision),
            "Approvisionnement cree avec succes."
        );
    }

    public function update(ApprovisionRequest $request, Approvision $approvision, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $approvision = $this->resolveAccessibleApprovision((int) $approvision->id, $scope);

        $data = $request->validated();
        $jauges = $data['appro_compartiment_jauges'] ?? null;
        unset($data['appro_compartiment_jauges']);

        $data['updated_by'] = Auth::id();

        if ($scope['is_station_scoped']) {
            $data['station_id'] = $scope['station_id'];
        }

        if ($this->referenceIsMissingOrEmpty($data)) {
            unset($data['reference']);
        }

        $oldApprovision = $approvision->replicate()->fill($approvision->getAttributes());

        DB::transaction(function () use ($approvision, $data, $jauges) {
            $approvision->update($data);

            if (is_array($jauges)) {
                $approvision->compartimentJauges()->delete();

                foreach ($jauges as $jauge) {
                    $jauge['created_by'] = Auth::id();
                    $approvision->compartimentJauges()->create($jauge);
                }
            }
        });

        $approvision->load($this->relations);

        logActivity("Mise a jour d'un approvisionnement", [
            'oldApprovision' => $oldApprovision->toArray(),
            'newApprovision' => $approvision->toArray(),
        ], $approvision);

        return $this->successResponse(
            new ApprovisionResource($approvision),
            "Approvisionnement mis a jour avec succes."
        );
    }

    public function destroy(Request $request, Approvision $approvision, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $this->resolveScope($request, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $approvision = $this->resolveAccessibleApprovision((int) $approvision->id, $scope);

        logActivity("Suppression d'un approvisionnement", $approvision->toArray(), $approvision);

        $approvision->delete();

        return $this->noContentSuccessResponse("Approvisionnement supprime avec succes.");
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

    private function resolveAccessibleApprovision(int $approvisionId, array $scope): Approvision
    {
        return Approvision::with($this->relations)
            ->when($scope['is_station_scoped'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($approvisionId);
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

    private function createWithReferenceRetry(array $data, bool $hasAutomaticReference): Approvision
    {
        $maxAttempts = $hasAutomaticReference ? 3 : 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($hasAutomaticReference) {
                $data['reference'] = $this->generateUniqueReference();
            }

            try {
                return Approvision::create($data);
            } catch (QueryException $exception) {
                $canRetry = $hasAutomaticReference
                    && $attempt < $maxAttempts
                    && $this->isReferenceCollision($exception);

                if (! $canRetry) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException("La creation de l'approvisionnement a echoue.");
    }

    private function isReferenceCollision(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? (string) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && str_contains($message, 'approvisions.reference');
    }

    private function generateUniqueReference(): string
    {
        do {
            $reference = 'APR-' . Str::upper(Str::random(6));
        } while (Approvision::where('reference', $reference)->exists());

        return $reference;
    }
}
