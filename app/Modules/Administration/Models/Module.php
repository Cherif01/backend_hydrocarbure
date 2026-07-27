<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'is_active'])]
class Module extends Model
{
    protected $table = 'modules';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function userModules()
    {
        return $this->hasMany(UserModule::class, 'module_id');
    }
}
