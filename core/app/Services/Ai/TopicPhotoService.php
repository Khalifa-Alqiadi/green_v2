<?php

namespace App\Services\Ai;

use App\Helpers\FileHelper;
use App\Models\Topic;
use App\Models\WebmasterSection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TopicPhotoService
{
    public function __construct(
        private readonly GeneratedImageAssetService $assets,
        private readonly SafeImageValidator $imageValidator,
        private readonly AiSettingsService $settings,
        private readonly AiAccessService $access,
    ) {}

    public function prepare(Request $request, WebmasterSection $section, ?Topic $topic = null): ?PreparedTopicPhoto
    {
        if ($request->hasFile('photo_file') && $request->filled('ai_generated_asset_id')) {
            throw ValidationException::withMessages(['photo_file' => __('backend.aiImageConflict')]);
        }

        if ($request->hasFile('photo_file')) {
            $request->validate(['photo_file' => 'file|mimes:'.config('filesystems.allowed_image_types')]);
            $info = FileHelper::uploadFile($request->file('photo_file'), 'topics', 1);

            return new PreparedTopicPhoto($info['name']);
        }

        if (!$request->filled('ai_generated_asset_id')) {
            return null;
        }

        abort_unless($section->photo_status
            && $this->settings->capabilityEnabled('images')
            && $this->access->canUse($request->user(), $section, $topic), 403);
        $asset = $this->assets->findAvailable($request->string('ai_generated_asset_id')->toString(), $request->user());
        $bytes = Storage::disk($asset->disk)->get($asset->path);
        $this->revalidate($asset->mime_type, $bytes, $asset->sha256);

        $extension = match ($asset->mime_type) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };
        $temporary = tempnam(sys_get_temp_dir(), 'smartend-ai-');
        if ($temporary === false || file_put_contents($temporary, $bytes) === false) {
            throw new \RuntimeException('AI image could not be prepared.');
        }

        try {
            $upload = new UploadedFile($temporary, 'generated.'.$extension, $asset->mime_type, null, true);
            $info = FileHelper::uploadFile($upload, 'topics', 1);
        } finally {
            @unlink($temporary);
        }

        return new PreparedTopicPhoto($info['name'], $asset);
    }

    public function commit(?PreparedTopicPhoto $prepared, Topic $topic): void
    {
        if (!$prepared?->asset) {
            return;
        }

        $prepared->asset->forceFill(['topic_id' => $topic->id, 'used_at' => now()])->save();
        Storage::disk($prepared->asset->disk)->delete($prepared->asset->path);
    }

    public function deleteOld(?string $filename): void
    {
        if (filled($filename) && $filename !== 'default.png') {
            FileHelper::deleteFile('topics/'.$filename);
        }
    }

    public function rollback(?PreparedTopicPhoto $prepared): void
    {
        if ($prepared) {
            FileHelper::deleteFile('topics/'.$prepared->filename);
        }
    }

    private function revalidate(string $mime, string $bytes, string $sha256): void
    {
        if (!hash_equals($sha256, hash('sha256', $bytes)) || strlen($bytes) > config('smartend_ai.max_image_bytes')) {
            throw ValidationException::withMessages(['ai_generated_asset_id' => __('backend.aiImageInvalid')]);
        }
        try {
            $this->imageValidator->validate($bytes, $mime);
        } catch (\RuntimeException) {
            throw ValidationException::withMessages(['ai_generated_asset_id' => __('backend.aiImageInvalid')]);
        }
    }
}
