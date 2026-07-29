<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\AffectationCiterneDepense;
use App\Modules\Transport\Requests\AffectationCiterneDepenseRequest;
use App\Modules\Transport\Resources\AffectationCiterneDepenseResource;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Support\Facades\Auth;

class AffectationCiterneDepenseController extends Controller
{
    use ApiResponses, CloudflareUpload;

    private array $relations = [
        'affectationCiterne.employee',
        'affectationCiterne.citerne',
        'createdBy',
        'updatedBy',
    ];

    public function index()
    {
        $depenses = AffectationCiterneDepense::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            AffectationCiterneDepenseResource::collection($depenses),
            "Liste des depenses d'affectation citerne chargee avec succes."
        );
    }

    public function show(AffectationCiterneDepense $affectation_citerne_depense)
    {
        $affectation_citerne_depense->load($this->relations);

        return $this->successResponse(
            new AffectationCiterneDepenseResource($affectation_citerne_depense),
            "Depense d'affectation citerne chargee avec succes."
        );
    }

    public function store(AffectationCiterneDepenseRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if ($request->hasFile('facture')) {
            $data['facture'] = $this->uploadFile($request->file('facture'), 'citerne_depense');
        }

        $depense = AffectationCiterneDepense::create($data)->load($this->relations);

        logActivity("Creation d'une depense d'affectation citerne", $depense->toArray(), $depense);

        return $this->successResponse(
            new AffectationCiterneDepenseResource($depense),
            "Depense d'affectation citerne creee avec succes."
        );
    }

    public function update(AffectationCiterneDepenseRequest $request, AffectationCiterneDepense $affectation_citerne_depense)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $oldDepense = $affectation_citerne_depense->replicate()->fill($affectation_citerne_depense->getAttributes());

        if ($request->hasFile('facture')) {
            $this->deleteFile($oldDepense->facture, 'citerne_depense');
            $data['facture'] = $this->uploadFile($request->file('facture'), 'citerne_depense');
        }

        $affectation_citerne_depense->update($data);
        $affectation_citerne_depense->load($this->relations);

        logActivity("Mise a jour d'une depense d'affectation citerne", [
            'oldDepense' => $oldDepense->toArray(),
            'newDepense' => $affectation_citerne_depense->toArray(),
        ], $affectation_citerne_depense);

        return $this->successResponse(
            new AffectationCiterneDepenseResource($affectation_citerne_depense),
            "Depense d'affectation citerne mise a jour avec succes."
        );
    }

    public function destroy(AffectationCiterneDepense $affectation_citerne_depense)
    {
        $affectation_citerne_depense->load($this->relations);

        logActivity("Suppression d'une depense d'affectation citerne", $affectation_citerne_depense->toArray(), $affectation_citerne_depense);

        $this->deleteFile($affectation_citerne_depense->facture, 'citerne_depense');
        $affectation_citerne_depense->delete();

        return $this->noContentSuccessResponse("Depense d'affectation citerne supprimee avec succes.");
    }
}
