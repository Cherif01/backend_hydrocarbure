<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'immatriculation',
    'type_citerne',
    'marque',
    'modele',
    'statut',
    'etat',
    'annee_fabrication',
    'capacite_nominale_litres',
    'capacite_utile_litres',
    'is_active',
    'created_by',
    'updated_by',
])]
class Citerne extends Model
{
    protected $table = 'citernes';

    protected $casts = [
        'capacite_nominale_litres' => 'decimal:2',
        'capacite_utile_litres' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function compartiments()
    {
        return $this->hasMany(CiterneCompartiment::class, 'citerne_id');
    }

    public function documents()
    {
        return $this->hasMany(CiterneDocument::class, 'citerne_id');
    }

    public function maintenances()
    {
        return $this->hasMany(MaintenanceCiterne::class, 'citerne_id');
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
