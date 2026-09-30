<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class LanguageSwitcher
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $backendPath = trim((string) config('smartend.backend_path'), '/');
        $isDashboardRequest = $backendPath !== ''
            && ($request->is($backendPath) || $request->is($backendPath.'/*'));
        $sessionKey = $isDashboardRequest ? 'dashboard_locale' : 'locale';
        $lang = session($sessionKey, $isDashboardRequest ? session('locale', '') : '');

        App::setLocale($lang !== '' ? $lang : Config::get('app.locale'));

        return $next($request);
    }
}
