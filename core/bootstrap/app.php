<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestsDuringMaintenance(except: ['mcp']);
        //
        $middleware->alias([
            'LanguageSwitcher' => \App\Http\Middleware\LanguageSwitcher::class,
            'ApiTokenAuth' => \App\Http\Middleware\ApiTokenAuth::class,
            'AdminRoutePermission' => \App\Http\Middleware\AdminRoutePermission::class,
            'AdminSameOriginAction' => \App\Http\Middleware\AdminSameOriginAction::class,
            'UseDemoTheme' => \App\Http\Middleware\UseDemoTheme::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['credentials']);
    })->create();

// Production installs keep the Laravel application in /core while the web
// server document root (assets, uploads and index.php) lives one level above.
$defaultPublicPath = str_replace('\\', '/', $app->publicPath());
$livePublicPath = preg_replace('#/core/public$#', '', $defaultPublicPath);

if ($livePublicPath !== null
    && $livePublicPath !== $defaultPublicPath
    && (is_dir($livePublicPath.'/assets') || is_file($livePublicPath.'/index.php'))) {
    $app->usePublicPath($livePublicPath);
}

return $app;
