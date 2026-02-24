<?php

use App\Mcp\Servers\SearchServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/search', SearchServer::class);
