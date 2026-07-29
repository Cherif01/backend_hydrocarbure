<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'citerne_id',
    'type_maintenance',
    'nature',
    'description',
    'date_prevue',
    'date_debut',
    'date_fin',
    'kilometrage_intervention',
    'cout',
    'prestataire',
    'facture_scan',
    'status',
    'created_by',
    'updated_by',
])]
class MaintenanceCiterne extends Model
{
    protected $table = 'maintenances_citerne';

    protected $casts = [
        'date_prevue' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'cout' => 'decimal:2',
    ];

    public function citerne()
    {
        return $this->belongsTo(Citerne::class, 'citerne_id');
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

