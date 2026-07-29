<?php

namespace App\Modules\Comptabilite\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['numero_compte', 'libelle', 'solde_initial', 'devise', 'is_active', 'created_by', 'updated_by', 'status'])]
class Compte extends Model
{
    protected $table = 'comptes';

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versements()
    {
        return $this->hasMany(Versement::class, 'compte_id');
    }
}
