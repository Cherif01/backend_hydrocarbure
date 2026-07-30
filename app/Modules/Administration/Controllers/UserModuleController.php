<?php

namespace App\Modules\Administration\Controllers;

use App\Events\SendMessageEvent;
use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\User;
use App\Modules\Administration\Models\UserModule;
use App\Modules\Administration\Requests\UserModuleRequest;
use App\Services\UserStationScopeService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserModuleController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $user_modules = UserModule::with(['module', 'user', 'createdBy', 'updatedBy'])->get();

        return $this->successResponse($user_modules, "Listes des affectations des modules aux utilisateurs");
    }

    public function show(UserModule $user_module)
    {
        return $this->successResponse($user_module->load(['module', 'user', 'createdBy', 'updatedBy']), "Affectation du module aux utilisateurs");
    }

    public function switchStatus(UserModule $user_module)
    {
        $user_module->is_active = !$user_module->is_active;
        $user_module->save();

        logActivity("Changement de statut de l'affectation du module aux utilisateurs", $user_module->toArray(), $user_module);

        return $this->successResponse($user_module, "Changement de statut de l'affectation du module aux utilisateurs");
    }

    public function sendAccessCode(UserModule $user_module)
    {
        $code_acces = $user_module->code_acces;
        $user = User::find($user_module->user_id);
        $telephone = $user?->telephone ?? '';

        if (empty($telephone)) {
            return $this->errorResponse("L'utilisateur n'a pas de numéro de téléphone");
        }

        $message = "Votre code d'acces au module {$user_module->module->name} est {$code_acces}";

        SendMessageEvent::dispatch($telephone, $message);

        logActivity("Envoi de code d'accès", $user_module->toArray(), $user_module);

        return $this->successResponse($user_module, "Envoi de code d'accès");
    }

    public function store(UserModuleRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['code_acces'] = rand(100000, 999999);

        // Prevent affectation of the same module to the same user multiple times
        if (UserModule::where('module_id', $data['module_id'])
            ->where('user_id', $data['user_id'])
            ->exists()
        ) {
            return $this->errorResponse("Ce module a déjà été affecté à cet'utilisateur");
        }

        $user_module = UserModule::create($data);

        logActivity("Affectation du module aux utilisateurs", $data, $user_module);

        return $this->successResponse($user_module, "Affectation du module aux utilisateurs");
    }

    public function update(UserModuleRequest $request, UserModule $user_module)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        // Prevent here the same module affectation to the same user multiple times, exclude the current update row
        if (UserModule::where('module_id', $data['module_id'])
            ->where('user_id', $data['user_id'])
            ->where('id', '<!=', $user_module->id)
            ->exists()
        ) {
            return $this->errorResponse("Ce module a déjà été affecté à cet'utilisateur");
        }

        $user_module->update($data);

        logActivity("Mise à jour de l'affectation du module aux utilisateurs", $data, $user_module);

        return $this->successResponse($user_module, "Mise à jour de l'affectation du module aux utilisateurs");
    }

    public function verifyAccessCode(Request $request, UserStationScopeService $stationScopeService)
    {
        $data = $request->validate([
            'code_acces' => 'required|string',
        ]);

        $user_module = UserModule::with('module')
            ->where('user_id', Auth::id())
            ->where('code_acces', $data['code_acces'])
            ->first();

        if (!$user_module) {
            return $this->errorResponse("Code d'accès invalide ou introuvable");
        }

        if (!$user_module->is_active) {
            return $this->errorResponse("Votre affectation a été désactivée");
        }

        if ($user_module->module?->name === 'gerant_station') {
            try {
                $scope = $stationScopeService->resolve($request->user());
            } catch (AuthorizationException $exception) {
                return $this->errorResponse($exception->getMessage(), 403);
            }

            Cache::put("user:{$request->user()->id}:station_id", $scope['station_id'], now()->addHours(8));
        }

        logActivity("Vérification de code d'accès", $user_module->toArray(), $user_module);

        return $this->successResponse($user_module, "Vérification de code réussie");
    }

    public function destroy(UserModule $user_module)
    {
        $user_module->delete();

        logActivity("Suppression de l'affectation du module aux utilisateurs", $user_module->toArray(), $user_module);

        return $this->noContentSuccessResponse("Suppression de l'affectation du module aux utilisateurs");
    }
}
