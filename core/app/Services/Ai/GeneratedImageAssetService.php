<?php

namespace App\Services\Ai;

use App\Models\AiGeneratedAsset;
use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GeneratedImageAssetService
{
    public function create(User $user, string $bytes, string $mime, int $width, int $height, string $prompt, ?AiUsageLog $usageLog = null): AiGeneratedAsset
    {
        try {
            if (Cache::add('smartend-ai:asset-prune', true, now()->addHour())) {
                $this->prune();
            }
        } catch (\Throwable) {
            // The scheduled command remains authoritative if an opportunistic prune cannot run.
        }

        $id = (string) Str::uuid();
        $disk = (string) config('smartend_ai.preview_disk', 'local');
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new \InvalidArgumentException('Unsupported generated image type.'),
        };
        $path = 'smartend-ai/'.$user->id.'/'.$id.'.'.$extension;
        if (!Storage::disk($disk)->put($path, $bytes, ['visibility' => 'private'])) {
            throw new \RuntimeException('Generated image preview could not be stored.');
        }

        try {
            return AiGeneratedAsset::query()->create([
                'id' => $id,
                'user_id' => $user->id,
                'ai_usage_log_id' => $usageLog?->id,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mime,
                'byte_size' => strlen($bytes),
                'width' => $width,
                'height' => $height,
                'sha256' => hash('sha256', $bytes),
                'optimized_prompt' => $prompt,
                'expires_at' => now()->addHours(config('smartend_ai.preview_ttl_hours', 24)),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    public function previewUrl(AiGeneratedAsset $asset): string
    {
        return URL::temporarySignedRoute('aiAssetPreview', $asset->expires_at, ['asset' => $asset->id]);
    }

    public function findAvailable(string $id, User $user): AiGeneratedAsset
    {
        $asset = AiGeneratedAsset::query()->findOrFail($id);
        abort_unless($asset->isAvailableFor($user->id), 404);
        abort_unless(Storage::disk($asset->disk)->exists($asset->path), 404);

        return $asset;
    }

    public function delete(AiGeneratedAsset $asset): void
    {
        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();
    }

    public function prune(): int
    {
        $count = 0;
        do {
            $assets = AiGeneratedAsset::query()->whereNull('used_at')->where('expires_at', '<=', now())
                ->orderBy('expires_at')->orderBy('id')->limit(100)->get();
            foreach ($assets as $asset) {
                $this->delete($asset);
                $count++;
            }
        } while ($assets->isNotEmpty());

        return $count;
    }
}
