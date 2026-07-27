<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\Module;
use App\Modules\Administration\Requests\ModuleRequest;
use App\Traits\ApiResponses;

class ModuleController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $modules = Module::orderBy('created_at', 'desc')->get();

        return $this->successResponse($modules, "Liste des modules chargée avec succès");
    }

    public function show(Module $module)
    {
        return $this->successResponse($module, "Module chargé avec succès");
    }

    public function store(ModuleRequest $request)
    {
        $data = $request->validated();

        $module = Module::create($data);

        logActivity("Création d'un nouveau module", $data, $module);

        return $this->successResponse($module, "Module créé avec succès");
    }

    public function update(ModuleRequest $request, Module $module)
    {
        $data = $request->validated();

        $oldModule = $module;

        $module->update($data);

        $newModule = $module;

        logActivity("Mise à jour d'un module", [
            'oldModule' => $oldModule,
            'newModule' => $newModule,
        ], $module);

        return $this->successResponse($module, "Module mis à jour avec succès");
    }

    public function destroy(Module $module)
    {
        logActivity("Suppression d'un module", $module->toArray(), $module);

        $module->delete();

        return $this->successResponse($module, "Module supprimé avec succès");
    }
}
