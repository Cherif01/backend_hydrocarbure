<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reference', 'station_id', 'libelle', 'description', 'is_active', 'created_by', 'updated_by'])]
class Pompe extends Model
{
    protected $table = 'pompes';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function pistolets()
    {
        return $this->hasMany(Pistolet::class, 'pompe_id');
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
