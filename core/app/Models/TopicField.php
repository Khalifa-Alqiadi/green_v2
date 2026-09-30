<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicField extends Model
{
    use HasFactory;

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}
