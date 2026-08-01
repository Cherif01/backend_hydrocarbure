<?php

namespace App\Traits;

use App\Modules\Comptabilite\Models\Caisse;
use App\Modules\Comptabilite\Models\Compte;
use App\Modules\ResourceHumaine\Models\Client;
use Illuminate\Support\Facades\DB;

trait Helper
{
    public function soldeClient($clientId)
    {
        $client = Client::find($clientId);
        if (!$client) {
            throw new \Exception('Client non trouve');
        }
        return  $client->depots()->sum('montant') - $client->paiementsCreances()->sum('montant');
    }

    public function soldeCaisse(Caisse $caisse): float
    {
        $soldeInitial = (float) ($caisse->solde_initial ?? 0);
        $operationsEntree = (float) ($caisse->operations_entree_sum ?? 0);
        $operationsSortie = (float) ($caisse->operations_sortie_sum ?? 0);
        $versementsSortie = (float) ($caisse->versements_sortie_sum ?? 0);
        $isPrimary = (bool) ($caisse->is_primary ?? false);
        $affectationsMontantRecu = (float) ($caisse->affectations_montant_recu_sum ?? 0);
        $paiementsCreances = (float) ($caisse->paiements_creances_sum ?? 0);

        $solde = $soldeInitial + $operationsEntree - $operationsSortie - $versementsSortie;

        if ($isPrimary) {
            $solde += $affectationsMontantRecu + $paiementsCreances;
        }

        return $solde;
    }

    public function soldeCaisseFromDb(int $caisseId): float
    {
        $caisse = Caisse::query()->select(['id', 'station_id', 'solde_initial'])->find($caisseId);
        if (! $caisse) {
            throw new \Exception('Caisse introuvable');
        }

        $soldeInitial = (float) ($caisse->solde_initial ?? 0);

        $operationsEntree = (float) DB::table('operations')
            ->join('type_operations', 'type_operations.id', '=', 'operations.type_operation_id')
            ->where('operations.caisse_id', $caisseId)
            ->whereNull('operations.deleted_at')
            ->where('type_operations.nature', true)
            ->sum('operations.montant');

        $operationsSortie = (float) DB::table('operations')
            ->join('type_operations', 'type_operations.id', '=', 'operations.type_operation_id')
            ->where('operations.caisse_id', $caisseId)
            ->whereNull('operations.deleted_at')
            ->where('type_operations.nature', false)
            ->sum('operations.montant');

        $versementsSortie = (float) DB::table('versements')
            ->where('caisse_id', $caisseId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['recu', 'confirmer'])
            ->sum('montant');

        $primaryCaisseId = Caisse::query()
            ->where('station_id', $caisse->station_id)
            ->min('id');

        $solde = $soldeInitial + $operationsEntree - $operationsSortie - $versementsSortie;

        if ((int) $primaryCaisseId === (int) $caisse->id) {
            $affectationsMontantRecu = (float) DB::table('affectation_pistolets')
                ->join('pistolets', 'pistolets.id', '=', 'affectation_pistolets.pistolet_id')
                ->join('pompes', 'pompes.id', '=', 'pistolets.pompe_id')
                ->where('pompes.station_id', $caisse->station_id)
                ->where('affectation_pistolets.is_active', false)
                ->sum('affectation_pistolets.montant_recu');

            $paiementsCreances = (float) DB::table('paiement_creances')
                ->join('creances', 'creances.id', '=', 'paiement_creances.creance_id')
                ->join('affectation_pistolets', 'affectation_pistolets.id', '=', 'creances.affectation_pistolet_id')
                ->join('pistolets', 'pistolets.id', '=', 'affectation_pistolets.pistolet_id')
                ->join('pompes', 'pompes.id', '=', 'pistolets.pompe_id')
                ->where('pompes.station_id', $caisse->station_id)
                ->whereNull('paiement_creances.deleted_at')
                ->sum('paiement_creances.montant');

            $solde += $affectationsMontantRecu + $paiementsCreances;
        }

        return $solde;
    }

    public function soldeCompteFromDb(int $compteId): float
    {
        $compte = Compte::query()->select(['id', 'solde_initial'])->find($compteId);
        if (! $compte) {
            throw new \Exception('Compte introuvable');
        }

        $soldeInitial = (float) ($compte->solde_initial ?? 0);

        $versements = (float) DB::table('versements')
            ->where('compte_id', $compteId)
            ->whereNull('deleted_at')
            ->where('status', 'confirmer')
            ->sum('montant');

        $transactionsIn = (float) DB::table('compte_transactions')
            ->where('compte_destination_id', $compteId)
            ->whereNull('deleted_at')
            ->sum('montant');

        $transactionsOut = (float) DB::table('compte_transactions')
            ->where('compte_source_id', $compteId)
            ->whereNull('deleted_at')
            ->sum('montant');

        return $soldeInitial + $versements + $transactionsIn - $transactionsOut;
    }
}
