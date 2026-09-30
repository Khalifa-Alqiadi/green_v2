<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminSameOriginAction
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() && $this->isStateChangingGetRoute((string) $request->route()?->getName())) {
            if (!$this->hasSameOriginHeader($request)) {
                abort(403);
            }
        }

        return $next($request);
    }

    private function isStateChangingGetRoute(string $routeName): bool
    {
        if ($routeName === '') {
            return false;
        }

        if (str_contains($routeName, 'Destroy') || str_contains($routeName, 'Clone')) {
            return true;
        }

        return in_array($routeName, [
            'cacheClear',
            'webmasterSEORepair',
            'topicsInstantIndex',
            'calendarUpdateAll',
        ], true);
    }

    private function hasSameOriginHeader(Request $request): bool
    {
        foreach (['Origin', 'Referer'] as $header) {
            $value = $request->headers->get($header);
            if (!$value) {
                continue;
            }

            $host = parse_url($value, PHP_URL_HOST);
            if ($host && strcasecmp($host, $request->getHost()) === 0) {
                return true;
            }
        }

        return false;
    }
}
