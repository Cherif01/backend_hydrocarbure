<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['station_id', 'user_id', 'is_active', 'created_by', 'updated_by'])]
class AffectationStation extends Model
{
    protected $table = 'affectation_stations';


    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
