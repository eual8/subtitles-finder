<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\EnsuresSearchAccess;
use App\Models\Playlist;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_playlists')]
#[Description('Returns all playlists available in the search filter.')]
#[IsReadOnly]
#[IsIdempotent]
class ListPlaylistsTool extends Tool
{
    use EnsuresSearchAccess;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->denyUnlessCanSearch($request)) {
            return $denied;
        }

        $playlists = Playlist::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(static fn (Playlist $playlist): array => [
                'id' => (int) $playlist->id,
                'title' => (string) $playlist->title,
            ])
            ->values()
            ->all();

        return Response::structured([
            'playlists' => $playlists,
            'total' => count($playlists),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'playlists' => $schema->array()
                ->required()
                ->items(
                    $schema->object([
                        'id' => $schema->integer()->required(),
                        'title' => $schema->string()->required(),
                    ])
                ),
            'total' => $schema->integer()->required()->min(0),
        ];
    }
}
