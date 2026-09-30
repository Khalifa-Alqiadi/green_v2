<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('_Loader_Web_Settings');
            foreach (Setting::query()->whereNotNull('theme_name')->pluck('theme_name') as $theme) {
                Cache::forget('_Loader_Web_Settings_'.$theme);
            }
        });
    }
}
