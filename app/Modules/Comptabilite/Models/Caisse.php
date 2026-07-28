<?php

namespace App\Modules\Comptabilite\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Station;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['station_id', 'reference', 'libelle', 'solde_initial', 'is_active', 'created_by', 'updated_by'])]
class Caisse extends Model
{
    protected $table = 'caisses';

    protected $casts = [
        'solde_initial' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function operations()
    {
        return $this->hasMany(Operation::class, 'caisse_id');
    }
}
