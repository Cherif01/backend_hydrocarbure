<?php

namespace App\Modules\Comptabilite\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['libelle', 'description', 'nature', 'is_active'])]
class TypeOperation extends Model
{
    protected $table = 'type_operations';

    protected $casts = [
        'nature' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function operations()
    {
        return $this->hasMany(Operation::class, 'type_operation_id');
    }
}
