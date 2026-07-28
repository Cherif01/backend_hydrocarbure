<?php

namespace App\Modules\Gestions\Middleware;

use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGestionsAccess
{
    use ApiResponses;

    public function __construct(
        private readonly UserStationScopeService $stationScopeService
    ) {}

    public function handle(Request $request, Closure $next, string $access = 'read'): Response
    {
        $user = $request->user();
        $isAdministrator = in_array($user?->role, ['admin', 'super_admin'], true);

        if ($access === 'admin' && ! $isAdministrator) {
            return $this->errorResponse(
                "Vous n'avez pas la permission d'effectuer cette operation.",
                403
            );
        }

        if ($isAdministrator) {
            $scope = [
                'is_station_scoped' => false,
                'station_id' => null,
                'affectation_station_id' => null,
            ];
        } else {
            try {
                $scope = $this->stationScopeService->resolve($user);
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }

            if (! $scope['is_station_scoped']) {
                return $this->errorResponse(
                    "Vous n'avez pas acces au module Gestions.",
                    403
                );
            }
        }

        $request->attributes->set('station_scope', $scope);

        return $next($request);
    }
}
