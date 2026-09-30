<?php

namespace App\Http\Middleware;

use Closure;
use Helper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Helper::GeneralWebmasterSettings("api_status")) {
            return response()->json([
                'code' => '0',
                'status' => 'error',
                'msg' => 'API is disabled',
            ], 403);
        }

        if ($request->header("token") !== Helper::GeneralWebmasterSettings("api_key")) {
            return response()->json([
                'code' => '-1',
                'status' => 'error',
                'msg' => 'Authentication failed',
            ], 401);
        }

        return $next($request);
    }
}
