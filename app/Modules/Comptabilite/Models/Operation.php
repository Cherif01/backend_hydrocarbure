<?php

namespace App\Modules\Comptabilite\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Station;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['type_operation_id', 'station_id', 'caisse_id', 'montant', 'commentaire', 'date_operation', 'created_by', 'updated_by'])]
class Operation extends Model
{
    use SoftDeletes;

    protected $table = 'operations';

    protected $casts = [
        'montant' => 'decimal:2',
        'date_operation' => 'datetime',
    ];

    public function typeOperation()
    {
        return $this->belongsTo(TypeOperation::class, 'type_operation_id');
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
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
}
