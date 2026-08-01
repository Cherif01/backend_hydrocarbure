<?php

namespace App\Modules\Comptabilite\Models;

use App\Modules\Administration\Models\User;
use App\Modules\ResourceHumaine\Models\Client;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable(['client_id', 'reference', 'libelle', 'commentaire', 'date_depot', 'montant', 'created_by', 'updated_by'])]
class ClientDepot extends Model
{
    use SoftDeletes;

    protected $table = 'client_depots';

    #[Override]
    protected function casts()
    {
        return [
            'date_depot' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
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
