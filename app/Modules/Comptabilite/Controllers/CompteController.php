<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\Compte;
use App\Modules\Comptabilite\Requests\CompteRequest;
use App\Modules\Comptabilite\Resources\CompteResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;

class CompteController extends Controller
{
    use ApiResponses;

    private array $relations = [
        'createdBy',
        'updatedBy',
        'versements.compte',
        'versements.caisse.station',
        'versements.user',
        'versements.createdBy',
        'versements.updatedBy',
    ];

    public function index()
    {
        $comptes = Compte::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            CompteResource::collection($comptes),
            "Liste des comptes chargee avec succes."
        );
    }

    public function show(Compte $compte)
    {
        $compte->load($this->relations);

        return $this->successResponse(
            new CompteResource($compte),
            "Compte charge avec succes."
        );
    }

    public function store(CompteRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $compte = Compte::create($data)->load($this->relations);

        logActivity("Creation d'un compte", $compte->toArray(), $compte);

        return $this->successResponse(
            new CompteResource($compte),
            "Compte cree avec succes."
        );
    }

    public function update(CompteRequest $request, Compte $compte)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $oldCompte = $compte->replicate()->fill($compte->getAttributes());

        $compte->update($data);
        $compte->load($this->relations);

        logActivity("Mise a jour d'un compte", [
            'oldCompte' => $oldCompte->toArray(),
            'newCompte' => $compte->toArray(),
        ], $compte);

        return $this->successResponse(
            new CompteResource($compte),
            "Compte mis a jour avec succes."
        );
    }

    public function destroy(Compte $compte)
    {
        $compte->load($this->relations);

        logActivity("Suppression d'un compte", $compte->toArray(), $compte);

        $compte->delete();

        return $this->noContentSuccessResponse("Compte supprime avec succes.");
    }
}
