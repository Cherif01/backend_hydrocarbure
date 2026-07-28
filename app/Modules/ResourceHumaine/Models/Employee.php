<?php

namespace App\Modules\ResourceHumaine\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\AffectationPistolet;
use App\Modules\Gestions\Models\Station;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'post_id', 'station_id', 'telephone', 'salaire_base', 'adresse', 'avatar', 'is_active', 'created_by', 'updated_by'])]
class Employee extends Model
{
    protected $table = 'employees';

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

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

    public function affectationsPistolets()
    {
        return $this->hasMany(AffectationPistolet::class, 'employee_id');
    }
}
