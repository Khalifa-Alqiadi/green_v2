<?php

namespace App\Http\Middleware;

use App\Services\DemoThemeService;
use App\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UseDemoTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! DemoThemeService::enabled($request)) {
            return $next($request);
        }

        ThemeService::setRequestTheme(DemoThemeService::selected($request));
        $verifyReturn = ! $request->routeIs('demoTheme.switch')
            && (bool) $request->session()->pull('demo_theme_verify_return', false);

        try {
            $response = $next($request);
            if ($verifyReturn && $response->getStatusCode() === 404 && $request->path() !== '/') {
                return redirect('/');
            }

            return $response;
        } catch (Throwable $exception) {
            $missingSelectedThemeView = $verifyReturn
                && str_contains($exception->getMessage(), 'View [themes.'.DemoThemeService::selected($request).'.')
                && str_contains($exception->getMessage(), 'not found');

            if ($missingSelectedThemeView && $request->path() !== '/') {
                return redirect('/');
            }

            throw $exception;
        } finally {
            ThemeService::setRequestTheme(null);
        }
    }
}
