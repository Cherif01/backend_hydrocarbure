<?php

namespace App\Modules\Transport\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'citerne_id',
    'type_document',
    'numero_document',
    'date_emission',
    'date_expiration',
    'fichier_scan',
    'created_by',
    'updated_by',
])]
class CiterneDocument extends Model
{
    protected $table = 'citerne_documents';

    protected $casts = [
        'date_emission' => 'date',
        'date_expiration' => 'date',
    ];

    public function citerne()
    {
        return $this->belongsTo(Citerne::class, 'citerne_id');
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

