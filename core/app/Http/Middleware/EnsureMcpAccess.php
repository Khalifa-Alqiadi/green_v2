<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user && method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        // Sanctum can resolve a session before inspecting the supplied Bearer token.
        if (! $token instanceof PersonalAccessToken) {
            return response()->json(['code' => 'UNAUTHENTICATED'], 401)->header('WWW-Authenticate', 'Bearer');
        }

        if (! $user instanceof User || (int) $user->status !== 1 || ! $user->mcp_enabled || ! $token->can('mcp:use')) {
            return response()->json(['code' => 'FORBIDDEN'], 403);
        }

        $response = $next($request);
        if ($request->attributes->get('mcp.forbidden', false)) {
            $response->setStatusCode(403);
        }

        return $response;
    }
}
