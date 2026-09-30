<?php

return [
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('MCP_ALLOWED_ORIGINS', ''))))),
    'rate_limit' => max(1, (int) env('MCP_RATE_LIMIT', 60)),
    'max_request_bytes' => max(1, (int) env('MCP_MAX_REQUEST_BYTES', 65536)),
];
