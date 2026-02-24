<?php

namespace App\Mcp\Tools;

use App\Data\FragmentSearchResult;
use App\Mcp\Tools\Concerns\EnsuresSearchAccess;
use App\Models\Fragment;
use App\Services\FragmentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[Name('search_fragments')]
#[Description('Searches fragment snippets with optional playlist/video filters and returns read links to full context.')]
#[IsReadOnly]
#[IsIdempotent]
class SearchFragmentsTool extends Tool
{
    use EnsuresSearchAccess;

    private const PER_PAGE = 20;

    public function __construct(
        private readonly FragmentSearchService $searchService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->denyUnlessCanSearch($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'min:1'],
            'playlistId' => ['nullable', 'integer', 'min:1'],
            'videoId' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'matchPhrase' => ['nullable', 'boolean'],
        ]);

        $query = trim((string) $validated['query']);
        $playlistId = isset($validated['playlistId']) ? (int) $validated['playlistId'] : null;
        $videoId = isset($validated['videoId']) ? (int) $validated['videoId'] : null;
        $page = isset($validated['page']) ? (int) $validated['page'] : 1;
        $matchPhrase = (bool) ($validated['matchPhrase'] ?? false);

        /** @var FragmentSearchResult $searchResult */
        $searchResult = $this->searchService->search(
            query: $query,
            playlistId: $playlistId,
            videoId: $videoId,
            page: $page,
            perPage: self::PER_PAGE,
            matchPhrase: $matchPhrase,
            withPreparedHighlights: true
        );

        $results = $searchResult->preparedHits
            ->map(function (array $hit): ?array {
                $fragment = $hit['model'] ?? null;

                if (! $fragment instanceof Fragment) {
                    return null;
                }

                $videoImage = null;

                try {
                    $videoImage = $fragment->video_image;
                } catch (Throwable) {
                    // Keep response resilient when object storage is unavailable.
                }

                return [
                    'fragmentId' => (int) $fragment->id,
                    'snippets' => collect($hit['snippets'] ?? [])->map(static fn ($snippet): string => (string) $snippet)->values()->all(),
                    'timeString' => $fragment->time_string,
                    'video' => [
                        'id' => (int) $fragment->video_id,
                        'title' => (string) ($fragment->video->title ?? ''),
                    ],
                    'videoImage' => $videoImage,
                    'readUrl' => $this->readUrl((int) $fragment->id),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $paginator = $searchResult->paginator;

        return Response::structured([
            'query' => $query,
            'filters' => [
                'playlistId' => $playlistId,
                'videoId' => $videoId,
                'matchPhrase' => $matchPhrase,
            ],
            'pagination' => [
                'page' => (int) $paginator->currentPage(),
                'perPage' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'lastPage' => (int) $paginator->lastPage(),
                'hasMorePages' => $paginator->hasMorePages(),
            ],
            'results' => $results,
            'resultCount' => count($results),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->required()
                ->min(1)
                ->description('Search phrase (same as searchQuery on /admin/search).'),
            'playlistId' => $schema->integer()
                ->nullable()
                ->min(1)
                ->description('Optional playlist filter ID.'),
            'videoId' => $schema->integer()
                ->nullable()
                ->min(1)
                ->description('Optional video filter ID. Works with selected playlist.'),
            'page' => $schema->integer()
                ->nullable()
                ->min(1)
                ->description('Page number, starts at 1.'),
            'matchPhrase' => $schema->boolean()
                ->nullable()
                ->description('If true, uses phrase-like bool_prefix behavior from the UI toggle.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required(),
            'filters' => $schema->object([
                'playlistId' => $schema->integer()->nullable(),
                'videoId' => $schema->integer()->nullable(),
                'matchPhrase' => $schema->boolean()->required(),
            ])->required(),
            'pagination' => $schema->object([
                'page' => $schema->integer()->required(),
                'perPage' => $schema->integer()->required(),
                'total' => $schema->integer()->required(),
                'lastPage' => $schema->integer()->required(),
                'hasMorePages' => $schema->boolean()->required(),
            ])->required(),
            'results' => $schema->array()
                ->required()
                ->items(
                    $schema->object([
                        'fragmentId' => $schema->integer()->required(),
                        'snippets' => $schema->array()->required()->items($schema->string()),
                        'timeString' => $schema->string()->nullable(),
                        'video' => $schema->object([
                            'id' => $schema->integer()->required(),
                            'title' => $schema->string()->required(),
                        ])->required(),
                        'videoImage' => $schema->string()->nullable(),
                        'readUrl' => $schema->string()->required(),
                    ])
                ),
            'resultCount' => $schema->integer()->required(),
        ];
    }

    private function readUrl(int $fragmentId): string
    {
        return url("/admin/fragments/{$fragmentId}/read");
    }
}
