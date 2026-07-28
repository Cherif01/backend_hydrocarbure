<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use App\Modules\ResourceHumaine\Models\Employee;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'employee_id',
    'pistolet_id',
    'index_ouverture',
    'index_fermeture',
    'litre_vendu',
    'prix_vente_jour',
    'litre_retouner',
    'montant_attentu',
    'montant_recu',
    'commentaire',
    'is_active',
    'created_by',
    'updated_by',
])]
class AffectationPistolet extends Model
{
    protected $table = 'affectation_pistolets';

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function creances()
    {
        return $this->hasMany(Creance::class, 'affectation_pistolet_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function pistolet()
    {
        return $this->belongsTo(Pistolet::class);
    }
}
