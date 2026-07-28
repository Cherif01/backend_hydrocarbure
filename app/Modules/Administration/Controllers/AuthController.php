<?php

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\User;
use App\Modules\Administration\Requests\LoginRequest;
use App\Modules\Administration\Requests\RegisterRequest;
use App\Modules\Administration\Requests\UpdatePasswordRequest;
use App\Modules\Administration\Requests\UpdateProfileRequest;
use App\Modules\Administration\Resources\UserResource;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponses, CloudflareUpload;

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->uploadImage($request->file('avatar'), 'avatars');
        }

        $user = User::create([
            'name' => $data['name'],
            'telephone' => $data['telephone'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'user',
            'avatar' => $data['avatar'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('user-token')->plainTextToken;

        $action = "Inscription de " . $user->name;
        logActivity($action, [
            'name' => $data['name'],
            'telephone' => $data['telephone'],
            'email' => $data['email'],
        ], $user);


        return $this->successResponseWithToken(new UserResource($user), $token, "Utilisateur créé avec succès.");
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('telephone', $data['telephone'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->errorResponse("Information invalide");
        }

        if (!$user->is_active) {
            return $this->errorResponse("Votre compte est désactivé");
        }

        $token = $user->createToken('user-token')->plainTextToken;

        $action = "Connection de " . $user->name;
        logActivity($action, ['telephone' => $data['telephone']], $user);


        return $this->successResponseWithToken(new UserResource($user), $token, "Utilisateur connecté avec succès.");
    }

    public function logout(Request $request)
    {
        $action = "Déconnection de " . $request->user()->name;
        logActivity($action);

        $request->user()->tokens()->delete();

        return $this->noContentSuccessResponse("Utilisateur deconnecté avec succès.");
    }

    public function switchStatus(Request $request, User $user)
    {
        // Only super_admin can perform this action
        if (!$request->user()->hasRole('super_admin')) {
            return $this->errorResponse(
                "Vous n'avez pas la permission de changer le statut d'un utilisateur.",
                403
            );
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $action = "Changement d'état de l'utilisateur " . $user->name;
        logActivity($action, $user->toArray(), $user);

        return $this->successResponse(
            new UserResource($user),
            "Statut de l'utilisateur changé avec succès."
        );
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $this->deleteImage($user->avatar, 'avatars');
            }
            $data['avatar'] = $this->uploadImage($request->file('avatar'), 'avatars');
        }

        $user->update($data);

        $action = "Mise à jour du profil " . $user->name;
        logActivity(
            $action,
            $data,
            $user
        );

        return $this->successResponse(
            new UserResource($user),
            "Profil mis à jour avec succès."
        );
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if (!Hash::check($data['current_password'], $user->password)) {
            return $this->errorResponse("Mot de passe actuel incorrect");
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        $action = "Mise à jour du mot de passe de " . $user->name;
        logActivity($action, $data, $user);

        return $this->successResponse(
            new UserResource($user),
            "Mot de passe mis à jour avec succès."
        );
    }

    public function me(Request $request)
    {
        return $this->successResponse(
            new UserResource($request->user()),
            "Utilisateur récupéré avec succès."
        );
    }

    public function users(Request $request)
    {
        $users = User::with('userModules')->orderBy('created_at', 'desc')->get();

        return $this->successResponse(
            UserResource::collection($users),
            "Utilisateurs récupérés avec succès."
        );
    }
}
