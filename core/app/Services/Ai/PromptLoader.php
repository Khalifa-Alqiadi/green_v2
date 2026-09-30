<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\File;
use RuntimeException;

class PromptLoader
{
    public function load(string $name, array $values = []): string
    {
        abort_unless(preg_match('/^[a-z0-9-]+$/', $name) === 1, 422, 'Invalid AI prompt.');
        $path = resource_path('ai/prompts/'.$name.'.md');
        if (!File::isFile($path)) {
            throw new RuntimeException('AI prompt file is missing: '.$name);
        }

        $prompt = File::get($path);
        preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', $prompt, $matches);
        foreach (array_unique($matches[1] ?? []) as $placeholder) {
            if (!array_key_exists($placeholder, $values)) {
                throw new RuntimeException('Unresolved AI prompt placeholder: '.$placeholder);
            }

            $encoded = json_encode($values[$placeholder], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $prompt = preg_replace_callback(
                '/{{\s*'.preg_quote($placeholder, '/').'\s*}}/',
                static fn (): string => $encoded,
                $prompt,
            );
        }

        return $prompt;
    }
}
