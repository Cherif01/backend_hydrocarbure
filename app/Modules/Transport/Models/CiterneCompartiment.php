<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Hydrocarbure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'citerne_id',
    'hydrocarbure_id',
    'numero_compartiment',
    'capacite_litres',
    'created_by',
    'updated_by',
])]
class CiterneCompartiment extends Model
{
    protected $table = 'citerne_compartiments';

    protected $casts = [
        'capacite_litres' => 'decimal:2',
    ];

    public function citerne()
    {
        return $this->belongsTo(Citerne::class, 'citerne_id');
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

