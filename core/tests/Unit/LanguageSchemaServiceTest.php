<?php

use App\Services\Dashboard\LanguageSchemaService;

test('language schema service normalizes valid two letter language codes', function () {
    $service = new LanguageSchemaService();

    expect($service->normalizeLanguageCode('EN'))->toBe('en')
        ->and($service->normalizeLanguageCode(' ar '))->toBe('ar');
});

test('language schema service rejects unsafe language codes', function () {
    $service = new LanguageSchemaService();

    expect($service->normalizeLanguageCode('eng'))->toBeNull()
        ->and($service->normalizeLanguageCode('e1'))->toBeNull()
        ->and($service->normalizeLanguageCode('../en'))->toBeNull()
        ->and($service->normalizeLanguageCode('e_'))->toBeNull();
});

test('language schema service manages localized Topic ALT without copying ALT content', function () {
    $definitions = (new ReflectionClass(LanguageSchemaService::class))->getReflectionConstant('LANGUAGE_TABLES')->getValue();
    $topics = collect($definitions)->firstWhere('table', 'topics');
    $columns = collect($topics['columns'])->pluck(0)->map(fn (string $prefix) => $prefix.'_zz')->all();

    expect($columns)->toContain('photo_alt_zz')
        ->and($topics['copy'])->not->toContain('photo_alt');
});
