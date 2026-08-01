<?php

namespace App\Modules\Comptabilite\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable(['reference', 'compte_source_id', 'compte_destination_id', 'montant', 'libelle', 'commentaire', 'date_transaction', 'created_by', 'updated_by'])]
class CompteTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'compte_transactions';

    #[Override]
    protected function casts()
    {
        return parent::casts() + [
            'date_transaction' => 'datetime',
            'montant' => 'decimal:2',
        ];
    }

    public function compteSource()
    {
        return $this->belongsTo(Compte::class, 'compte_source_id');
    }

    public function compteDestination()
    {
        return $this->belongsTo(Compte::class, 'compte_destination_id');
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
