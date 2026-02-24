<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('insufficient_data_policy')]
#[Description('Policy for handling low-confidence or incomplete results from MCP search tools.')]
class InsufficientDataPolicyPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $topic = trim((string) $request->get('topic', ''));

        $topicBlock = $topic !== ''
            ? "Topic: {$topic}\n\n"
            : '';

        return Response::text($topicBlock.<<<'TEXT'
Apply this policy when data is incomplete, weak, or contradictory.

When evidence is insufficient:
1) Explicitly state: "Недостаточно данных в MCP-источнике для уверенного вывода."
2) List exactly what is missing:
   - missing timeframe,
   - missing direct quote,
   - missing context window,
   - missing coverage across playlists/videos.
3) Execute follow-up actions:
   - broaden or narrow query in `search_fragments`,
   - move through pages,
   - open `read_fragment_window` for top results.
4) If still insufficient, ask a precise clarifying question to the user.

Never do:
- No guesses.
- No external knowledge insertion.
- No confident claims without citation.

Confidence guidance:
- High: multiple consistent fragments + context windows.
- Medium: single fragment or weak context.
- Low: no direct supporting fragments.
TEXT);
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'topic',
                description: 'Optional topic for applying the insufficient-data policy.',
                required: false,
            ),
        ];
    }
}
