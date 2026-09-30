<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webmaster_settings', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false);
            $table->string('ai_text_provider')->nullable();
            $table->string('ai_text_model')->nullable();
            $table->longText('ai_provider_credentials')->nullable();
            $table->string('ai_default_tone')->default('professional');
            $table->string('ai_custom_tone')->nullable();
            $table->string('ai_default_content_length')->default('medium');
            $table->decimal('ai_temperature', 3, 2)->nullable();
            $table->unsignedInteger('ai_max_tokens')->default(4000);
            $table->unsignedSmallInteger('ai_timeout_seconds')->default(120);
            $table->boolean('ai_content_enabled')->default(true);
            $table->boolean('ai_seo_enabled')->default(true);
            $table->boolean('ai_translation_enabled')->default(true);
            $table->boolean('ai_images_enabled')->default(false);
            $table->string('ai_image_provider')->nullable();
            $table->string('ai_image_model')->nullable();
            $table->string('ai_image_quality')->default('medium');
            $table->string('ai_image_orientation')->default('automatic');
            $table->string('ai_image_style')->default('automatic');
            $table->boolean('ai_image_use_theme_colors')->default(true);
            $table->text('ai_image_brand_instructions')->nullable();
            $table->text('ai_image_negative_instructions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('webmaster_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_enabled', 'ai_text_provider', 'ai_text_model', 'ai_provider_credentials',
                'ai_default_tone', 'ai_custom_tone', 'ai_default_content_length',
                'ai_temperature', 'ai_max_tokens', 'ai_timeout_seconds',
                'ai_content_enabled', 'ai_seo_enabled', 'ai_translation_enabled',
                'ai_images_enabled', 'ai_image_provider', 'ai_image_model',
                'ai_image_quality', 'ai_image_orientation', 'ai_image_style',
                'ai_image_use_theme_colors', 'ai_image_brand_instructions',
                'ai_image_negative_instructions',
            ]);
        });
    }
};
