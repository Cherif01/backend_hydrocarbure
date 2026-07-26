<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\Role;
use App\Modules\Administration\Requests\RoleRequest;
use App\Traits\ApiResponses;

class RoleController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $roles = Role::with('users')->orderBy('created_at', 'desc')->get();

        return $this->successResponse($roles, "Liste des roles chargée avec succès");
    }

    public function show(Role $role)
    {
        return $this->successResponse($role->load('users'), "Role chargé avec succès");
    }

    public function store(RoleRequest $request)
    {
        $data = $request->validated();

        $role = Role::create($data);

        logActivity("Création d'un nouveau role", $data, $role);

        return $this->successResponse($role, "Role créé avec succès");
    }

    public function update(RoleRequest $request, Role $role)
    {
        $data = $request->validated();

        $oldRole = $role;

        $role->update($data);

        $newRole = $role;

        logActivity("Mise à jour d'un role", [
            'oldRole' => $oldRole,
            'newRole' => $newRole,
        ], $role);

        return $this->successResponse($role, "Role mis à jour avec succès");
    }

    public function destroy(Role $role)
    {
        logActivity("Suppression d'un role", $role->toArray(), $role);

        $role->delete();

        return $this->successResponse($role, "Role supprimé avec succès");
    }
}
