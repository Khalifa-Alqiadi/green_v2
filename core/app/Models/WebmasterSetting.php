<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebmasterSetting extends Model
{
    use HasFactory;

    protected $hidden = [
        'ai_provider_credentials',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'ai_provider_credentials' => 'encrypted:array',
            'ai_temperature' => 'float',
            'ai_max_tokens' => 'integer',
            'ai_timeout_seconds' => 'integer',
            'ai_content_enabled' => 'boolean',
            'ai_seo_enabled' => 'boolean',
            'ai_translation_enabled' => 'boolean',
            'ai_images_enabled' => 'boolean',
            'ai_image_use_theme_colors' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('_Loader_WebmasterSettings');
        });

        static::deleted(function () {
            Cache::forget('_Loader_WebmasterSettings');
        });
    }
}
