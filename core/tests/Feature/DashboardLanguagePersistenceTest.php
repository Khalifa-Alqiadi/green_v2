<?php

use App\Http\Controllers\LanguageController;
use App\Http\Middleware\LanguageSwitcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

test('dashboard language selection does not replace the public website locale', function () {
    session(['locale' => 'en']);

    (new LanguageController)->locale('ar');

    expect(session('dashboard_locale'))->toBe('ar')
        ->and(session('locale'))->toBe('en');
});

test('dashboard requests prefer the isolated dashboard locale', function () {
    session([
        'locale' => 'en',
        'dashboard_locale' => 'ar',
    ]);

    $request = Request::create('/'.config('smartend.backend_path').'/modules');
    $request->setLaravelSession(app('session')->driver());

    (new LanguageSwitcher)->handle($request, fn () => new Response);

    expect(app()->getLocale())->toBe('ar');
});

test('public requests continue to use the public website locale', function () {
    session([
        'locale' => 'en',
        'dashboard_locale' => 'ar',
    ]);

    $request = Request::create('/');
    $request->setLaravelSession(app('session')->driver());

    (new LanguageSwitcher)->handle($request, fn () => new Response);

    expect(app()->getLocale())->toBe('en');
});
