<?php

namespace App\Modules\Comptabilite\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable(['compte_id', 'caisse_id', 'type', 'user_id', 'montant', 'date_versement', 'date_reception', 'commentaire', 'status', 'created_by', 'updated_by'])]
class Versement extends Model
{
    use SoftDeletes;

    protected $table = 'versements';

    #[Override]
    public function casts()
    {
        return [
            'montant' => 'decimal:2',
            'date_versement' => 'datetime',
            'date_reception' => 'datetime',
        ];
    }

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
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
