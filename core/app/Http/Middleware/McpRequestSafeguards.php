<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class McpRequestSafeguards
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $origin = $request->header('Origin');
            $normalized = $origin === null ? null : $this->origin($origin);
            $allowed = array_filter(array_map($this->origin(...), config('smartend_mcp.allowed_origins', [])));

            if ($origin !== null && ($normalized === null || ! in_array($normalized, $allowed, true))) {
                $response = response()->json(['code' => 'FORBIDDEN'], 403);
            } elseif (strlen($request->getContent()) > config('smartend_mcp.max_request_bytes')) {
                $response = response()->json(['code' => 'REQUEST_TOO_LARGE'], 413);
            } else {
                // Route priority otherwise places authentication ahead of throttling.
                // Use the native named limiter once, without changing global priority.
                $response = app(ThrottleRequests::class)->handle($request, $next, 'mcp');
            }
        } catch (AuthenticationException) {
            $response = response()->json(['code' => 'UNAUTHENTICATED'], 401)->header('WWW-Authenticate', 'Bearer');
        } catch (HttpResponseException $exception) {
            $response = $exception->getResponse();
        } catch (ThrottleRequestsException $exception) {
            $response = response()->json(['code' => 'RATE_LIMITED'], 429, $exception->getHeaders());
        } catch (Throwable) {
            // Do not forward infrastructure exceptions to debug pages or secret-bearing logs.
            $response = response()->json(['code' => 'INTERNAL_ERROR'], 500);
        }

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    private function origin(string $origin): ?string
    {
        if (preg_match('/\Ahttps?:\/\/[^\s\/\\\\?#@]+\z/i', $origin) !== 1) {
            return null;
        }
        try {
            $parts = parse_url($origin);
        } catch (\ValueError) {
            return null;
        }
        if (! is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            return null;
        }

        return $scheme.'://'.strtolower($parts['host']).':'.$port;
    }
}
