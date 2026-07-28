<?php

namespace App\Modules\ResourceHumaine\Models;

use App\Modules\Administration\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['libelle', 'is_active', 'created_by', 'updated_by'])]
class Post extends Model
{
    protected $table = 'posts';

    public function employees()
    {
        return $this->hasMany(Employee::class, 'post_id');
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
