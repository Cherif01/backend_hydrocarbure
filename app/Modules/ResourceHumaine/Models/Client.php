<?php

namespace App\Modules\ResourceHumaine\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Comptabilite\Models\PaiementCreance;
use App\Modules\Gestions\Models\Creance;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'telephone', 'email', 'adresse', 'avatar', 'is_active', 'created_by', 'updated_by'])]
class Client extends Model
{
    protected $table = 'clients';

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hydrocarbures()
    {
        return $this->hasMany(ClientHydrocarbure::class, 'client_id');
    }

    public function creances()
    {
        return $this->hasMany(Creance::class, 'client_id');
    }

    public function paiementsCreances()
    {
        return $this->hasMany(PaiementCreance::class, 'client_id');
    }
}
