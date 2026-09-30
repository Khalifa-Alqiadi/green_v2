<?php

namespace App\Services\Ai;

use App\Models\AiGeneratedAsset;

class PreparedTopicPhoto
{
    public function __construct(
        public readonly string $filename,
        public readonly ?AiGeneratedAsset $asset = null,
    ) {}
}
