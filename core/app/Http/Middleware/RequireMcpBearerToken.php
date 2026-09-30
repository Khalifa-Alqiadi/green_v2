<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMcpBearerToken
{
    public static function hasBearer(Request $request): bool
    {
        return preg_match('/\ABearer [A-Za-z0-9._~+\/|=-]+\z/i', (string) $request->header('Authorization')) === 1;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::hasBearer($request)) {
            return response()->json(['code' => 'UNAUTHENTICATED'], 401)->header('WWW-Authenticate', 'Bearer');
        }

        return $next($request);
    }
}
