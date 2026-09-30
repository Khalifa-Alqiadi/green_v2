<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'position_ar',
        'position_en',
        'photo',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'author_topic')->withTimestamps();
    }
}
