<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Comptabilite\Models\Caisse;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reference', 'libelle', 'description', 'adresse', 'ville', 'longitude', 'latitude', 'image', 'is_active', 'created_by', 'updated_by'])]
class Station extends Model
{
    protected $table = 'stations';

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function affectations()
    {
        return $this->hasMany(AffectationStation::class, 'station_id');
    }

    public function pompes()
    {
        return $this->hasMany(Pompe::class, 'station_id');
    }

    public function cuves()
    {
        return $this->hasMany(Cuve::class, 'station_id');
    }

    public function caisses()
    {
        return $this->hasMany(Caisse::class, 'station_id');
    }
}
