<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGeneratedAsset extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['path', 'optimized_prompt', 'sha256'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'ai_usage_log_id' => 'integer',
            'topic_id' => 'integer',
            'byte_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isAvailableFor(int $userId): bool
    {
        return $this->user_id === $userId
            && $this->used_at === null
            && $this->expires_at?->isFuture();
    }
}
