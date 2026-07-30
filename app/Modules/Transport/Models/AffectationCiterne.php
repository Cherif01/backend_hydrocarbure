<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Station;
use App\Modules\ResourceHumaine\Models\Employee;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'station_id',
    'employee_id',
    'citerne_id',
    'date_affectation',
    'date_depart_prevu',
    'date_arrive_prevu',
    'date_depart_reel',
    'date_arrive_reel',
    'date_retour_prevu',
    'date_retour_reel',
    'ville_depart',
    'ville_destination',
    'longitude_depart',
    'latitude_depart',
    'longitude_destination',
    'latitude_destination',
    'status',
    'created_by',
    'updated_by',
])]
class AffectationCiterne extends Model
{
    protected $table = 'affectation_citernes';

    protected $casts = [
        'date_affectation' => 'date',
        'date_depart_prevu' => 'date',
        'date_arrive_prevu' => 'date',
        'date_depart_reel' => 'date',
        'date_arrive_reel' => 'date',
        'date_retour_prevu' => 'date',
        'date_retour_reel' => 'date',
        'longitude_depart' => 'decimal:2',
        'latitude_depart' => 'decimal:2',
        'longitude_destination' => 'decimal:2',
        'latitude_destination' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function citerne()
    {
        return $this->belongsTo(Citerne::class, 'citerne_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function depenses()
    {
        return $this->hasMany(AffectationCiterneDepense::class, 'affectation_citerne_id');
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
