<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

    'default' => 'openai',
    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'openai',
    'default_for_reranking' => 'cohere',

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. These are
    | non-secret driver defaults. Manage provider settings and encrypted keys
    | in the dashboard; AI credentials are never read from .env.
    |
    */

    // Runtime providers are populated only from encrypted database settings.
    'providers' => [],

    // Non-secret SDK defaults; credentials and endpoint overrides belong in AI Settings.
    'provider_defaults' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => null,
            'url' => 'https://api.anthropic.com/v1',
        ],

        'azure' => [
            'driver' => 'azure',
            'key' => null,
            'url' => null,
            'api_version' => '2025-04-01-preview',
            'deployment' => 'gpt-4o',
            'embedding_deployment' => 'text-embedding-3-small',
            'image_deployment' => 'gpt-image-1',
            'store' => false,
        ],

        'bedrock' => [
            'driver' => 'bedrock',
            'region' => 'us-east-1',
            'key' => null,
            'access_key_id' => null,
            'secret_access_key' => null,
            'session_token' => null,
            'use_default_credential_provider' => false,
            'assume_role' => [
                'arn' => null,
                'session_name' => null,
                'duration_seconds' => null,
                'external_id' => null,
            ],
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => null,
        ],

        'deepseek' => [
            'driver' => 'deepseek',
            'key' => null,
        ],

        'eleven' => [
            'driver' => 'eleven',
            'key' => null,
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => null,
            'url' => 'https://generativelanguage.googleapis.com/v1beta/',
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => null,
        ],

        'jina' => [
            'driver' => 'jina',
            'key' => null,
        ],

        'mistral' => [
            'driver' => 'mistral',
            'key' => null,
        ],

        'ollama' => [
            'driver' => 'ollama',
            'key' => '',
            'url' => 'http://localhost:11434',
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => null,
            'url' => 'https://api.openai.com/v1',
            'store' => false,
        ],

        'openai-compatible' => [
            'driver' => 'openai-compatible',
            'url' => null,
            'key' => null,
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'key' => null,
        ],

        'voyageai' => [
            'driver' => 'voyageai',
            'key' => null,
        ],

        'xai' => [
            'driver' => 'xai',
            'key' => null,
        ],
    ],

];
