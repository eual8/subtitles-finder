<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\EnsuresSearchAccess;
use App\Services\VideoService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_playlist_videos')]
#[Description('Returns videos for a selected playlist, matching the search page video filter behavior.')]
#[IsReadOnly]
#[IsIdempotent]
class ListPlaylistVideosTool extends Tool
{
    use EnsuresSearchAccess;

    public function __construct(
        private readonly VideoService $videoService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->denyUnlessCanSearch($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'playlistId' => ['required', 'integer', 'min:1'],
        ]);

        $playlistId = (int) $validated['playlistId'];

        $videos = $this->videoService
            ->getVideosForSelect($playlistId)
            ->map(static fn (string $title, int $id): array => [
                'id' => (int) $id,
                'title' => $title,
            ])
            ->values()
            ->all();

        return Response::structured([
            'playlistId' => $playlistId,
            'videos' => $videos,
            'total' => count($videos),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'playlistId' => $schema->integer()
                ->required()
                ->min(1)
                ->description('Playlist ID from list_playlists.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'playlistId' => $schema->integer()->required()->min(1),
            'videos' => $schema->array()
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
