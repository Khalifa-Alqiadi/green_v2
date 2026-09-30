<?php

use App\Http\Middleware\EnsureMcpAccess;
use App\Http\Middleware\McpRequestSafeguards;
use App\Http\Middleware\RequireMcpBearerToken;
use App\Mcp\Servers\SmartendDashboardServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', SmartendDashboardServer::class)
    ->middleware([
        McpRequestSafeguards::class,
        RequireMcpBearerToken::class,
        'auth:sanctum',
        EnsureMcpAccess::class,
    ]);
