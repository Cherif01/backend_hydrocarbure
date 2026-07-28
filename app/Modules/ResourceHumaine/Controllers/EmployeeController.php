<?php

namespace App\Modules\ResourceHumaine\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ResourceHumaine\Models\Employee;
use App\Modules\ResourceHumaine\Requests\EmployeeRequest;
use App\Modules\ResourceHumaine\Resources\EmployeeResource;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    use ApiResponses, CloudflareUpload;

    public function index(Request $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $employees = Employee::with(['post', 'station', 'createdBy', 'updatedBy'])
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            EmployeeResource::collection($employees),
            "Liste des employes chargee avec succes."
        );
    }

    public function show(Request $request, Employee $employee, UserStationScopeService $stationScopeService)
    {
        try {
            $employee = $this->resolveScopedEmployee($request, $employee->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        return $this->successResponse(
            new EmployeeResource($employee),
            "Employe charge avec succes."
        );
    }

    public function store(EmployeeRequest $request, UserStationScopeService $stationScopeService)
    {
        try {
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if ($scope['station_id']) {
            $data['station_id'] = $scope['station_id'];
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->uploadImage($request->file('avatar'), 'employees');
        }

        $employee = Employee::create($data)
            ->load(['post', 'station', 'createdBy', 'updatedBy']);

        logActivity("Creation d'un nouvel employe", $employee->toArray(), $employee);

        return $this->successResponse(
            new EmployeeResource($employee),
            "Employe cree avec succes."
        );
    }

    public function update(EmployeeRequest $request, Employee $employee, UserStationScopeService $stationScopeService)
    {
        try {
            $employee = $this->resolveScopedEmployee($request, $employee->id, $stationScopeService);
            $scope = $stationScopeService->resolve($request->user());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if ($scope['station_id']) {
            $data['station_id'] = $scope['station_id'];
        }

        if ($request->hasFile('avatar')) {
            if ($employee->avatar) {
                $this->deleteImage($employee->avatar, 'employees');
            }

            $data['avatar'] = $this->uploadImage($request->file('avatar'), 'employees');
        }

        $oldEmployee = $employee->replicate()->fill($employee->getAttributes());

        $employee->update($data);
        $employee->load(['post', 'station', 'createdBy', 'updatedBy']);

        logActivity("Mise a jour d'un employe", [
            'oldEmployee' => $oldEmployee->toArray(),
            'newEmployee' => $employee->toArray(),
        ], $employee);

        return $this->successResponse(
            new EmployeeResource($employee),
            "Employe mis a jour avec succes."
        );
    }

    public function destroy(Request $request, Employee $employee, UserStationScopeService $stationScopeService)
    {
        try {
            $employee = $this->resolveScopedEmployee($request, $employee->id, $stationScopeService);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        if ($employee->avatar) {
            $this->deleteImage($employee->avatar, 'employees');
        }

        logActivity("Suppression d'un employe", $employee->toArray(), $employee);

        $employee->delete();

        return $this->noContentSuccessResponse("Employe supprime avec succes.");
    }

    private function resolveScopedEmployee(Request $request, int $employeeId, UserStationScopeService $stationScopeService): Employee
    {
        $scope = $stationScopeService->resolve($request->user());

        return Employee::with(['post', 'station', 'createdBy', 'updatedBy'])
            ->when($scope['station_id'], function ($query) use ($scope) {
                $query->where('station_id', $scope['station_id']);
            })
            ->findOrFail($employeeId);
    }
}
