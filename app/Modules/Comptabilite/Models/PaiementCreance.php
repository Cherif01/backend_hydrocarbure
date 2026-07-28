<?php

namespace App\Modules\Comptabilite\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Creance;
use App\Modules\ResourceHumaine\Models\Client;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reference', 'client_id', 'creance_id', 'montant', 'mode_paiement', 'date_paiement', 'commentaire', 'created_by', 'updated_by'])]
class PaiementCreance extends Model
{
    use SoftDeletes;

    protected $table = 'paiement_creances';

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function creance()
    {
        return $this->belongsTo(Creance::class, 'creance_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
