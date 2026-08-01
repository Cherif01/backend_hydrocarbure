<?php

namespace App\Modules\Comptabilite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comptabilite\Models\CompteTransaction;
use App\Modules\Comptabilite\Requests\CompteTransactionRequest;
use App\Modules\Comptabilite\Resources\CompteTransactionResource;
use App\Traits\ApiResponses;
use App\Traits\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CompteTransactionController extends Controller
{
    use ApiResponses, Helper;

    private array $relations = [
        'compteSource',
        'compteDestination',
        'createdBy',
        'updatedBy',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $this->isAdmin($user) && ! $this->hasActiveModule($user, 'comptabilite')) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $transactions = CompteTransaction::with($this->relations)
            ->orderBy('date_transaction', 'desc')
            ->get();

        return $this->successResponse(
            CompteTransactionResource::collection($transactions),
            'Liste des transactions de comptes chargee avec succes.'
        );
    }

    public function show(Request $request, CompteTransaction $compte_transaction)
    {
        $user = $request->user();

        if (! $this->isAdmin($user) && ! $this->hasActiveModule($user, 'comptabilite')) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $compte_transaction->load($this->relations);

        return $this->successResponse(
            new CompteTransactionResource($compte_transaction),
            'Transaction de compte chargee avec succes.'
        );
    }

    public function store(CompteTransactionRequest $request)
    {
        $user = $request->user();

        if (! $this->isAdmin($user) && ! $this->hasActiveModule($user, 'comptabilite')) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if (! array_key_exists('reference', $data) || $data['reference'] === null || $data['reference'] === '') {
            $data['reference'] = $this->generateUniqueReference();
        }

        if ((int) $data['compte_source_id'] === (int) $data['compte_destination_id']) {
            return $this->errorResponse("Le compte source doit etre different du compte destination.", 422);
        }

        $montant = (float) ($data['montant'] ?? 0);
        $solde = $this->soldeCompteFromDb((int) $data['compte_source_id']);
        if (($solde - $montant) < 0) {
            return $this->errorResponse("Solde du compte source insuffisant.", 400);
        }

        $transaction = CompteTransaction::create($data)->load($this->relations);

        logActivity("Creation d'une transaction de compte", $transaction->toArray(), $transaction);

        return $this->successResponse(
            new CompteTransactionResource($transaction),
            'Transaction de compte creee avec succes.'
        );
    }

    public function update(CompteTransactionRequest $request, CompteTransaction $compte_transaction)
    {
        $user = $request->user();

        if (! $this->isAdmin($user) && ! $this->hasActiveModule($user, 'comptabilite')) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if (array_key_exists('reference', $data) && ($data['reference'] === null || $data['reference'] === '')) {
            unset($data['reference']);
        }

        $oldSourceId = (int) $compte_transaction->compte_source_id;
        $oldMontant = (float) ($compte_transaction->montant ?? 0);

        $newSourceId = (int) ($data['compte_source_id'] ?? $oldSourceId);
        $newMontant = (float) ($data['montant'] ?? $oldMontant);
        $newDestinationId = (int) ($data['compte_destination_id'] ?? $compte_transaction->compte_destination_id);

        if ($newSourceId === $newDestinationId) {
            return $this->errorResponse("Le compte source doit etre different du compte destination.", 422);
        }

        $soldeNewSourceCurrent = $this->soldeCompteFromDb($newSourceId);
        if ($newSourceId === $oldSourceId) {
            $soldeAfter = ($soldeNewSourceCurrent + $oldMontant) - $newMontant;
            if ($soldeAfter < 0) {
                return $this->errorResponse("Solde du compte source insuffisant.", 400);
            }
        } else {
            $soldeAfter = $soldeNewSourceCurrent - $newMontant;
            if ($soldeAfter < 0) {
                return $this->errorResponse("Solde du compte source insuffisant.", 400);
            }
        }

        $oldTransaction = $compte_transaction->replicate()->fill($compte_transaction->getAttributes());

        $compte_transaction->update($data);
        $compte_transaction->load($this->relations);

        logActivity("Mise a jour d'une transaction de compte", [
            'oldCompteTransaction' => $oldTransaction->toArray(),
            'newCompteTransaction' => $compte_transaction->toArray(),
        ], $compte_transaction);

        return $this->successResponse(
            new CompteTransactionResource($compte_transaction),
            'Transaction de compte mise a jour avec succes.'
        );
    }

    public function destroy(Request $request, CompteTransaction $compte_transaction)
    {
        $user = $request->user();

        if (! $this->isAdmin($user) && ! $this->hasActiveModule($user, 'comptabilite')) {
            return $this->errorResponse("Vous n'avez pas la permission d'effectuer cette operation.", 403);
        }

        $compte_transaction->load($this->relations);

        logActivity("Suppression d'une transaction de compte", $compte_transaction->toArray(), $compte_transaction);

        $compte_transaction->delete();

        return $this->noContentSuccessResponse('Transaction de compte supprimee avec succes.');
    }

    private function generateUniqueReference(): string
    {
        do {
            $reference = 'CPTR-' . Str::upper(Str::random(6));
        } while (CompteTransaction::where('reference', $reference)->exists());

        return $reference;
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
}
