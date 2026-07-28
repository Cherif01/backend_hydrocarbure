<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Comptabilite\Models\PaiementCreance;
use App\Modules\ResourceHumaine\Models\Client;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'affectation_pistolet_id', 'date_creance', 'total_litre', 'montant', 'commentaire', 'created_by', 'updated_by'])]
class Creance extends Model
{
    protected $table = 'creances';

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function affectationPistolet()
    {
        return $this->belongsTo(AffectationPistolet::class, 'affectation_pistolet_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function paiementsCreances()
    {
        return $this->hasMany(PaiementCreance::class, 'creance_id');
    }
}
