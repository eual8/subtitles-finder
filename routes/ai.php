<?php

use App\Mcp\Servers\SearchServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Compatibility endpoint for clients that probe GET transport (for example ChatGPT web MCP client).
// Returning SSE is valid per MCP Streamable HTTP transport and avoids hard failures on 405 probes.
Route::get('/mcp/search', function () {
    return response()->stream(
        static function (): void {
            echo ": connected\n\n";
            flush();
        },
        200,
        [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]
    );
});

Mcp::web('/mcp/search', SearchServer::class);
