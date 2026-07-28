<?php

namespace App\Modules\ResourceHumaine\Models;

use App\Modules\Administration\Models\User;
use App\Modules\Gestions\Models\Hydrocarbure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'hydrocarbure_id', 'max_litre', 'prix', 'is_active', 'created_by', 'updated_by'])]
class ClientHydrocarbure extends Model
{
    protected $table = 'client_hydrocarbures';

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
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
