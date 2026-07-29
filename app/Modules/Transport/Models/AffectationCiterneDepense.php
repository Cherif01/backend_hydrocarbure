<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'affectation_citerne_id',
    'libelle',
    'description',
    'montant',
    'date_depense',
    'facture',
    'created_by',
    'updated_by',
])]
class AffectationCiterneDepense extends Model
{
    protected $table = 'affectation_citerne_depenses';

    protected $casts = [
        'montant' => 'decimal:2',
        'date_depense' => 'date',
    ];

    public function affectationCiterne()
    {
        return $this->belongsTo(AffectationCiterne::class, 'affectation_citerne_id');
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

