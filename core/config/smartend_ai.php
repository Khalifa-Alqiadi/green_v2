<?php

return [
    'text_rate_limit' => (int) env('SMARTEND_AI_TEXT_RATE_LIMIT', 20),
    'image_rate_limit' => (int) env('SMARTEND_AI_IMAGE_RATE_LIMIT', 3),
    'max_input_characters' => (int) env('SMARTEND_AI_MAX_INPUT_CHARACTERS', 50000),
    'max_image_bytes' => (int) env('SMARTEND_AI_MAX_IMAGE_BYTES', 15 * 1024 * 1024),
    'max_image_pixels' => (int) env('SMARTEND_AI_MAX_IMAGE_PIXELS', 40000000),
    'preview_disk' => env('SMARTEND_AI_PREVIEW_DISK', 'local'),
    'preview_ttl_hours' => (int) env('SMARTEND_AI_PREVIEW_TTL_HOURS', 24),

    'text_providers' => [
        'anthropic', 'azure', 'bedrock', 'deepseek', 'gemini', 'groq',
        'mistral', 'ollama', 'openai', 'openai-compatible', 'openrouter', 'xai',
    ],
    'image_providers' => ['azure', 'bedrock', 'gemini', 'openai', 'openrouter', 'xai'],

    'tones' => ['professional', 'formal', 'friendly', 'informative', 'marketing', 'creative', 'custom'],
    'content_lengths' => ['short', 'medium', 'long'],
    'image_styles' => ['automatic', 'photorealistic', 'illustration', 'corporate', 'minimal', '3d', 'editorial', 'abstract'],
    'image_orientations' => ['automatic', 'landscape', 'square', 'portrait'],
    'image_qualities' => ['low', 'medium', 'high'],
    'content_operations' => [
        'write', 'rewrite', 'improve', 'make_engaging', 'optimize_title_seo',
        'shorten', 'expand', 'fix_grammar', 'change_tone', 'redesign',
    ],

    'default_negative_instructions' => 'No written text, article titles, captions, logos, watermarks, unreadable typography, or unnecessary UI screenshots.',
];
