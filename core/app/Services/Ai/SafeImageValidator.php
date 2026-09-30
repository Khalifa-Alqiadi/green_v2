<?php

namespace App\Services\Ai;

use RuntimeException;

class SafeImageValidator
{
    /** @return array{0:string,1:int,2:int} */
    public function validate(string $bytes, ?string $declaredMime = null): array
    {
        if ($bytes === '' || strlen($bytes) > (int) config('smartend_ai.max_image_bytes')) {
            throw new RuntimeException('Generated image data is invalid or too large.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || ($declaredMime && $declaredMime !== $mime)) {
            throw new RuntimeException('Generated image type is not allowed.');
        }

        $dimensions = @getimagesizefromstring($bytes);
        if (!$dimensions || ((int) $dimensions[0] * (int) $dimensions[1]) > (int) config('smartend_ai.max_image_pixels')) {
            throw new RuntimeException('Generated image dimensions are invalid or too large.');
        }

        $expected = match ($dimensions[2] ?? null) {
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_WEBP => 'image/webp',
            default => null,
        };
        if ($expected !== $mime || !$this->hasExactContainerBoundary($bytes, $mime)) {
            throw new RuntimeException('Generated image content does not match its type.');
        }

        return [$mime, (int) $dimensions[0], (int) $dimensions[1]];
    }

    private function hasExactContainerBoundary(string $bytes, string $mime): bool
    {
        return match ($mime) {
            'image/jpeg' => str_starts_with($bytes, "\xFF\xD8") && str_ends_with($bytes, "\xFF\xD9"),
            'image/png' => str_starts_with($bytes, "\x89PNG\r\n\x1A\n")
                && str_ends_with($bytes, "\x00\x00\x00\x00IEND\xAE\x42\x60\x82"),
            'image/webp' => strlen($bytes) >= 12
                && substr($bytes, 0, 4) === 'RIFF'
                && substr($bytes, 8, 4) === 'WEBP'
                && ((unpack('Vsize', substr($bytes, 4, 4))['size'] ?? -1) + 8) === strlen($bytes),
            default => false,
        };
    }
}
