<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Station;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'reference',
    'affectation_citerne_id',
    'station_id',
    'date_approvision',
    'total_litre_theorique',
    'total_litre_reel',
    'created_by',
    'updated_by',
])]
class Approvision extends Model
{
    protected $table = 'approvisions';

    protected $casts = [
        'date_approvision' => 'datetime',
        'total_litre_theorique' => 'integer',
        'total_litre_reel' => 'integer',
    ];

    public function affectationCiterne()
    {
        return $this->belongsTo(AffectationCiterne::class, 'affectation_citerne_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function compartimentJauges()
    {
        return $this->hasMany(ApproCompartimentJauge::class, 'approvision_id');
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

