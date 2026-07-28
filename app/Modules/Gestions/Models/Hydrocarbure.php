<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use App\Modules\ResourceHumaine\Models\ClientHydrocarbure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['libelle', 'prix_achat', 'prix_vente', 'created_by', 'updated_by'])]
class Hydrocarbure extends Model
{
    protected $table = 'hydrocarbures';

    protected function casts(): array
    {
        return [
            'prix_achat' => 'decimal:2',
            'prix_vente' => 'decimal:2',
        ];
    }

    public function pistolets()
    {
        return $this->hasMany(Pistolet::class, 'hydrocarbure_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function clients()
    {
        return $this->hasMany(ClientHydrocarbure::class, 'hydrocarbure_id');
    }
}
