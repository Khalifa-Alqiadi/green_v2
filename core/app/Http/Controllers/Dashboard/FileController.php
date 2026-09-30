<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class FileController extends Controller
{
    private array $blockedExtensions = [
        'bash', 'bat', 'cgi', 'conf', 'db', 'env', 'htaccess', 'htpasswd', 'ini', 'lock',
        'log', 'phar', 'php', 'php3', 'php4', 'php5', 'phtml', 'pl', 'py', 'rb', 'sh',
        'sqlite', 'sql', 'toml', 'yaml', 'yml', 'zsh',
    ];

    public function view($path = "")
    {
        $path = $this->normalizePublicPath($path);
        if ($path === null) {
            return redirect()->route("frontendRoute", ["part1" => Helper::currentLanguage()->code]);
        }

        $disk = config('filesystems.default');
        if (!Storage::disk($disk)->exists($path)) {
            return redirect()->route("frontendRoute", ["part1" => Helper::currentLanguage()->code]);
        }

        $mime = Storage::disk($disk)->mimeType($path);
        $content = Storage::disk($disk)->get($path);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $isImage = Str::startsWith($mime, 'image/');
        $width = $this->imageDimension(\request()->query('w'));
        $height = $this->imageDimension(\request()->query('h'));
        $fit = \request()->query('r');

        if ($isImage && ($width || $height)) {
            try {
                if (@class_exists("Imagick")) {
                    if ($width == "") {
                        $width = $height;
                    }
                    if ($height == "") {
                        $height = $width;
                    }
                    $resizedPath = 'cache/'.$width.'x'.$height.'/'.$path;
                    if (!Storage::disk($disk)->exists($resizedPath)) {
                        // Get full temp file path
                        $tempInputPath = Storage::disk($disk)->path($path);
                        $tempOutputPath = Storage::disk($disk)->path($resizedPath);

                        // Ensure directory exists
                        $resizedDir = dirname($tempOutputPath);
                        if (!file_exists($resizedDir)) {
                            mkdir($resizedDir, 0755, true);
                        }
                        // Resize using Spatie
                        if ($fit == "fit") {
                            Image::load($tempInputPath)->fit(Fit::Crop, $width, $height)->save($tempOutputPath);
                        } else {
                            Image::load($tempInputPath)->width($width)->height($height)->save($tempOutputPath);
                        }
                    }
                    $content = Storage::disk($disk)->get($resizedPath);
                }
            } catch (\Exception $e) {

            }
        }

        // custom render for SVG
        if ($extension === 'svg') {
            $stream = Storage::disk($disk)->readStream($path);
            return response()->stream(function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, 200, array_merge($this->publicCacheHeaders($disk, $path), [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="'.basename($path).'"',
                'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; sandbox",
                'X-Content-Type-Options' => 'nosniff',
            ]));
        }

        return Response::make($content, 200, array_merge($this->publicCacheHeaders($disk, $path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]));
    }


    public function download($path = "")
    {
        $path = $this->normalizePublicPath($path);
        if ($path === null) {
            return redirect()->route("frontendRoute", ["part1" => Helper::currentLanguage()->code]);
        }

        $disk = config('filesystems.default');
        if (!Storage::disk($disk)->exists($path)) {
            return redirect()->route("frontendRoute", ["part1" => Helper::currentLanguage()->code]);
        }

        return Storage::disk($disk)->download($path);
    }

    private function normalizePublicPath($path): ?string
    {
        $path = (string) $path;
        for ($i = 0; $i < 3; $i++) {
            $decoded = rawurldecode($path);
            if ($decoded === $path) {
                break;
            }
            $path = $decoded;
        }

        $path = trim(str_replace('\\', '/', $path), "/ \t\n\r\0\x0B");
        if ($path === '' || Str::contains($path, ["\0", '..'])) {
            return null;
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || Str::startsWith($segment, '.')) {
                return null;
            }
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, $this->blockedExtensions, true)) {
            return null;
        }

        return $path;
    }

    private function imageDimension($value): int|string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_numeric($value)) {
            return '';
        }

        return max(1, min(2500, (int) $value));
    }

    private function publicCacheHeaders(string $disk, string $path): array
    {
        $headers = [
            'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
        ];

        try {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', Storage::disk($disk)->lastModified($path)).' GMT';
        } catch (\Exception $e) {

        }

        return $headers;
    }
}
