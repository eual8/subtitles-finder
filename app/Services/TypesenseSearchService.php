<?php

namespace App\Services;

use App\Support\TypesenseSearchResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Typesense\Client;

final class TypesenseSearchService
{
    private const string COLLECTION_NAME = 'fragments';

    private const int TIMEOUT = 90;

    private const int VECTOR_K = 20;

    private Client $client;

    public function __construct()
    {
        $this->client = $this->makeClient();
    }

    /**
     * Генерирует эмбеддинг для текста поискового запроса
     */
    public function generateEmbedding(string $text): array
    {
        $url = config('services.sentence_embeddings.url');

        if (empty($url)) {
            throw new RuntimeException('Sentence embeddings service URL is not configured.');
        }

        $response = Http::timeout(self::TIMEOUT)->post($url, [
            'texts' => [$text],
            'normalize' => true,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Sentence embeddings request failed with status %d: %s',
                $response->status(),
                $response->body()
            ));
        }

        $body = $response->json();
        $embeddings = $body['embeddings'] ?? null;

        if (! is_array($embeddings) || count($embeddings) !== 1) {
            throw new RuntimeException('Invalid embeddings payload received from sentence embeddings service.');
        }

        return array_map('floatval', $embeddings[0]);
    }

    /**
     * Выполняет векторный поиск в Typesense
     */
    public function search(string $query, ?int $playlistId, ?int $videoId, int $page, int $perPage = 20): TypesenseSearchResult
    {
        // Генерируем эмбеддинг для поискового запроса
        $queryEmbedding = $this->generateEmbedding($query);

        // Формируем параметры поиска
        $searchParams = [
            'q' => '*',
            'vector_query' => sprintf('text_vector:([], k:%d, distance_threshold: 1.0)', self::VECTOR_K),
            'per_page' => $perPage,
            'page' => $page,
        ];

        // Добавляем фильтрацию по playlist_id или video_id
        $filters = [];

        if ($videoId !== null) {
            $filters[] = sprintf('video_id:=%d', $videoId);
        } elseif ($playlistId !== null) {
            // Получаем все video_id для данного плейлиста
            $videoIds = \App\Models\Video::where('playlist_id', $playlistId)->pluck('id')->toArray();

            if (! empty($videoIds)) {
                $filters[] = sprintf('video_id:[%s]', implode(',', $videoIds));
            }
        }

        if (! empty($filters)) {
            $searchParams['filter_by'] = implode(' && ', $filters);
        }

        try {
            // Используем multi_search для больших векторов
            $multiSearchParams = [
                'searches' => [
                    array_merge($searchParams, [
                        'collection' => self::COLLECTION_NAME,
                        'vector_query' => sprintf(
                            'text_vector:(%s, k:%d)',
                            json_encode($queryEmbedding),
                            self::VECTOR_K
                        ),
                    ]),
                ],
            ];

            $results = $this->client->multiSearch->perform($multiSearchParams);

            // Извлекаем первый результат из multi_search
            $searchResult = $results['results'][0] ?? [];

            return new TypesenseSearchResult($searchResult, $page, $perPage);
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf(
                'Typesense search failed: %s',
                $exception->getMessage()
            ), 0, $exception);
        }
    }

    private function makeClient(): Client
    {
        $config = config('typesense');

        if (empty($config['api_key'])) {
            throw new RuntimeException('TYPESENSE_API_KEY is not configured.');
        }

        if (empty($config['nodes'])) {
            throw new RuntimeException('Typesense nodes are not configured (see config/typesense.php).');
        }

        return new Client([
            'api_key' => $config['api_key'],
            'nodes' => $config['nodes'],
            'connection_timeout_seconds' => Arr::get($config, 'connection_timeout_seconds', 2),
        ]);
    }
}
