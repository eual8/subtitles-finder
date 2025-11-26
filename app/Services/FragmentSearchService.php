<?php

namespace App\Services;

use App\Data\FragmentSearchResult;
use App\Models\Fragment;
use App\Transformers\FragmentHighlightTransformer;
use Elastic\ScoutDriverPlus\Builders\BoolQueryBuilder;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;

final class FragmentSearchService
{
    private const array SEARCH_FIELDS = [
        'text^3',
        'text.fallback^2',
        'text.ngram^1',
    ];

    private const array HIGHLIGHT_OPTIONS = [
        'pre_tags' => ['<mark><b>'],
        'post_tags' => ['</b></mark>'],
        // allow highlighting across multi_match fields
        'require_field_match' => false,
        // render only one compact fragment; if nothing matches – return a small excerpt
        'fragment_size' => 220,
        'number_of_fragments' => 1,
        'no_match_size' => 220,
        'type' => 'unified',
        // highlight text using matches from all sub‑fields
        'matched_fields' => ['text', 'text.fallback', 'text.ngram'],
    ];

    public function __construct(
        private readonly FragmentHighlightTransformer $highlightTransformer,
    ) {}

    public function search(string $query, ?int $playlistId, ?int $videoId, int $page, int $perPage = 20, bool $matchPhrase = false, bool $withPreparedHighlights = false): Paginator|FragmentSearchResult
    {
        $searchQuery = $this->buildBaseQuery($query, $playlistId, $videoId, $matchPhrase);

        $paginator = $this->runSearch($searchQuery, $perPage, $page, true);

        if (! $withPreparedHighlights) {
            return $paginator;
        }

        $preparedHits = $this->highlightTransformer->transform($paginator, $query);

        return new FragmentSearchResult($paginator, $preparedHits);
    }

    /**
     * Поиск фрагментов для экспорта (без пагинации)
     */
    public function searchForExport(string $query, ?int $playlistId = null, ?int $videoId = null, bool $matchPhrase = false, int $limit = 1000): Paginator
    {
        $searchQuery = $this->buildBaseQuery($query, $playlistId, $videoId, $matchPhrase);

        // Используем такой же запрос как в search, но с большим лимитом и без подсветки
        return $this->runSearch($searchQuery, $limit, 1, false);
    }

    /**
     * Собирает общий bool-запрос с фильтрами по плейлисту/видео.
     */
    private function buildBaseQuery(string $query, ?int $playlistId, ?int $videoId, bool $matchPhrase): BoolQueryBuilder
    {
        if ($matchPhrase === true) {
            // bool_prefix работает с search_as_you_type и поддерживает «набор по буквам» для последнего токена
            $mustSearchBlock = Query::multiMatch()
                ->type('bool_prefix')
                ->fields(self::SEARCH_FIELDS);
        } else {
            // обычный match по нескольким полям
            $mustSearchBlock = Query::multiMatch()
                ->type('best_fields')
                ->fields(self::SEARCH_FIELDS);
        }

        $mustSearchBlock->query($query);
        $searchQuery = Query::bool()->must($mustSearchBlock);

        if ($playlistId !== null) {
            $searchQuery->must(
                Query::term()
                    ->field('playlist_id')
                    ->value($playlistId)
            );
        }

        if ($videoId !== null) {
            $searchQuery->must(
                Query::term()
                    ->field('video_id')
                    ->value($videoId)
            );
        }

        return $searchQuery;
    }

    /**
     * Выполняет запрос с нужной пагинацией и опциональной подсветкой.
     */
    private function runSearch(BoolQueryBuilder $searchQuery, int $perPage, int $page = 1, bool $withHighlight = false): Paginator
    {
        $builder = Fragment::searchQuery($searchQuery)
            ->load(['video']);

        if ($withHighlight) {
            $builder->highlight('text', self::HIGHLIGHT_OPTIONS)
                ->highlight('text.fallback', self::HIGHLIGHT_OPTIONS)
                ->highlight('text.ngram', self::HIGHLIGHT_OPTIONS);
        }

        return $builder->paginate($perPage, 'page', $page);
    }
}
