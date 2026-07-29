<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['station_id', 'hydrocarbure_id', 'reference', 'libelle', 'capacite', 'is_active', 'created_by', 'updated_by'])]
class Cuve extends Model
{
    use SoftDeletes;

    protected $table = 'cuves';

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
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
