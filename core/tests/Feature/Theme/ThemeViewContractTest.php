<?php

use Illuminate\Support\Facades\File;

function themeBladePaths(string $theme): array
{
    $root = resource_path('views/themes/'.$theme);

    return collect(File::allFiles($root))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php' && str_ends_with($file->getFilename(), '.blade.php'))
        ->map(fn (SplFileInfo $file): string => str_replace('\\', '/', $file->getRelativePathname()))
        ->sort()
        ->values()
        ->all();
}

test('every native theme retains the complete classic view contract', function () {
    $classicViews = themeBladePaths('classic');

    foreach (['modern', 'velocity'] as $theme) {
        $themeViews = themeBladePaths($theme);

        expect(array_values(array_diff($classicViews, $themeViews)))
            ->toBe([], ucfirst($theme).' is missing a Classic theme view.');
    }
});
