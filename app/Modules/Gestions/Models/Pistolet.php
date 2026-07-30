<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pompe_id', 'hydrocarbure_id', 'libelle', 'is_active', 'created_by', 'updated_by'])]
class Pistolet extends Model
{
    protected $table = 'pistolets';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pompe()
    {
        return $this->belongsTo(Pompe::class, 'pompe_id');
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

    public function affectationsPistolets()
    {
        return $this->hasMany(AffectationPistolet::class, 'pistolet_id');
    }

    public function latestAffectationPistolet()
    {
        return $this->hasOne(AffectationPistolet::class, 'pistolet_id')->ofMany(['id' => 'max'], function ($query) {
            $query->where('is_active', false);
        });
    }
}
