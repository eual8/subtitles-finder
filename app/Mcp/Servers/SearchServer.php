<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ListPlaylistsTool;
use App\Mcp\Tools\ListPlaylistVideosTool;
use App\Mcp\Tools\ReadFragmentWindowTool;
use App\Mcp\Tools\SearchFragmentsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Search Server')]
#[Version('0.0.1')]
#[Instructions('Provides search and fragment-reading tools that mirror the existing admin search workflow: choose playlist/video filters, search snippets, open full read context, and move forward/backward between read windows.')]
class SearchServer extends Server
{
    protected array $tools = [
        ListPlaylistsTool::class,
        ListPlaylistVideosTool::class,
        SearchFragmentsTool::class,
        ReadFragmentWindowTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
