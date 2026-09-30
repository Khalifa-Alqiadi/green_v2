<?php
namespace App\Helpers;

use App;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\File as LocalFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use URL;

class FileHelper
{
    private static array $blockedExtensions = [
        'bash', 'bat', 'cgi', 'conf', 'db', 'env', 'htaccess', 'htpasswd', 'ini', 'lock',
        'log', 'phar', 'php', 'php3', 'php4', 'php5', 'phtml', 'pl', 'py', 'rb', 'sh',
        'sqlite', 'sql', 'toml', 'yaml', 'yml', 'zsh',
    ];

    static function uploadFile(UploadedFile $file, string $subDir = "", $isImg = 0,$imgWidth=0,$imgHeight=0): array
    {
        $disk = config('filesystems.default');
        $subDir = self::normalizeRelativePath($subDir, false);
        $extension = self::safeExtension($file->getClientOriginalExtension());
        $filename = Str::uuid().'.'.$extension;
        $filTitle = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension());
        if ($isImg) {
            // Process an owned, extension-bearing local copy before sending it to any configured disk.
            $placeholder = tempnam(sys_get_temp_dir(), 'smartend-image-');
            if ($placeholder === false) {
                throw new \RuntimeException('Unable to prepare the image upload.');
            }
            $localPath = $placeholder.'.'.$extension;
            @unlink($placeholder);
            if (!copy($file->getRealPath(), $localPath)) {
                @unlink($localPath);
                throw new \RuntimeException('Unable to prepare the image upload.');
            }
            try {
                Helper::imageResize($localPath, $imgWidth, $imgHeight);
                Helper::imageOptimize($localPath);
                $storedPath = Storage::disk($disk)->putFileAs($subDir, new LocalFile($localPath), $filename);
            } finally {
                @unlink($localPath);
            }
        } else {
            $storedPath = $file->storeAs($subDir, $filename, $disk);
        }
        if ($storedPath === false) {
            throw new \RuntimeException('Unable to store the uploaded file.');
        }
        $physicalURL = self::physicalUploadsPath($subDir, $filename);
        return [
            'path' => $storedPath,
            'url' => $physicalURL,
            'name' => $filename,
            'title' => $filTitle
        ];
    }
    static function cloneFile(string $fileName, string $subDir = ""): ?array
    {
        $disk = config('filesystems.default');
        $subDir = self::normalizeRelativePath($subDir, false);
        $fileName = self::normalizeRelativePath($fileName);
        $extension = self::safeExtension(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = Str::uuid() . '.' . $extension;
        if (!Storage::disk($disk)->exists(self::joinRelativePath($subDir, $fileName))) {
            return null;
        }
        Storage::disk($disk)->copy(self::joinRelativePath($subDir, $fileName), self::joinRelativePath($subDir, $newFileName));
        $fullPath = rtrim((string) config('filesystems.uploads_path'), '/');
        if ($subDir !== '') {
            $fullPath .= '/'.$subDir;
        }
        $physicalURL = Storage::disk($disk)->url(self::joinRelativePath($subDir, $newFileName));

        return [
            'path' => $fullPath."/".$newFileName,
            'url' => $physicalURL,
            'name' => $newFileName,
            'title' => "",
        ];
    }

    static function deleteFile(string $path): bool
    {
        $disk = config('filesystems.default');
        try {
            $path = self::normalizeRelativePath($path);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        return false;
    }

    private static function safeExtension(string $extension): string
    {
        $extension = strtolower(trim($extension));
        if ($extension === '' || in_array($extension, self::$blockedExtensions, true)) {
            throw new \InvalidArgumentException('Unsafe file extension.');
        }

        return $extension;
    }

    private static function normalizeRelativePath(string $path, bool $requireExtension = true): string
    {
        $path = trim(str_replace('\\', '/', rawurldecode($path)), "/ \t\n\r\0\x0B");
        if ($path === '') {
            if (!$requireExtension) {
                return '';
            }
            throw new \InvalidArgumentException('Unsafe file path.');
        }

        if (Str::contains($path, ["\0", '..'])) {
            throw new \InvalidArgumentException('Unsafe file path.');
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || Str::startsWith($segment, '.')) {
                throw new \InvalidArgumentException('Unsafe file path.');
            }
        }

        if ($requireExtension) {
            self::safeExtension(pathinfo($path, PATHINFO_EXTENSION));
        }

        return $path;
    }

    private static function joinRelativePath(string $directory, string $fileName): string
    {
        return $directory === '' ? $fileName : $directory.'/'.$fileName;
    }

    private static function physicalUploadsPath(string $subDir, string $filename): string
    {
        $basePath = rtrim((string) config('filesystems.uploads_path'), '/');
        return $basePath.'/'.self::joinRelativePath($subDir, $filename);
    }
}

?>
