<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Hydrocarbure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'approvision_id',
    'hydrocarbure_id',
    'num_compartiment',
    'valeur_jauge',
    'volume_reel',
    'volume_theorique',
    'created_by',
    'updated_by',
])]
class ApproCompartimentJauge extends Model
{
    protected $table = 'appro_compartiment_jauges';

    protected $casts = [
        'valeur_jauge' => 'decimal:2',
        'volume_reel' => 'decimal:2',
        'volume_theorique' => 'decimal:2',
    ];

    public function approvision()
    {
        return $this->belongsTo(Approvision::class, 'approvision_id');
    }

    public function hydrocarbure()
    {
        return $this->belongsTo(Hydrocarbure::class, 'hydrocarbure_id');
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
