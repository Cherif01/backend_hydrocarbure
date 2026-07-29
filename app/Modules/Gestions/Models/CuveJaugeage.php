<?php

namespace App\Modules\Gestions\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'cuve_id',
    'date_jauge',
    'valeur_jauge',
    'volume_reel',
    'volume_theorique',
    'commentaire',
    'created_by',
    'updated_by',
])]
class CuveJaugeage extends Model
{
    protected $table = 'cuve_jaugeages';

    protected $casts = [
        'date_jauge' => 'datetime',
        'valeur_jauge' => 'decimal:2',
        'volume_reel' => 'decimal:2',
        'volume_theorique' => 'decimal:2',
    ];

    public function cuve()
    {
        return $this->belongsTo(Cuve::class, 'cuve_id');
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

