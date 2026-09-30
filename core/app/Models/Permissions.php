<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permissions extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ai_use' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany('App\Models\User', 'permissions_id');
    }
}
