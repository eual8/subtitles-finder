<?php

namespace App\Services;

use App\Models\Fragment;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;

final class FragmentSearchService
{
    private const array SEARCH_FIELDS = [
        'text^3',
        'text.fallback^2',
        'text.ngram^1',
    ];

    public function search(string $query, ?int $playlistId, ?int $videoId, int $page, int $perPage = 20, bool $matchPhrase = false): Paginator
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
        // Основной bool
        $searchQuery = Query::bool()->must($mustSearchBlock);

        // Фильтр по playlist_id
        if ($playlistId !== null) {
            $searchQuery->must(
                Query::term()
                    ->field('playlist_id')
                    ->value($playlistId)
            );
        }

        // Фильтр по video_id
        if ($videoId !== null) {
            $searchQuery->must(
                Query::term()
                    ->field('video_id')
                    ->value($videoId)
            );
        }

        $highlightOptions = [
            'pre_tags' => ['<mark><b>'],
            'post_tags' => ['</b></mark>'],
            // allow highlighting across multi_match fields
            'require_field_match' => false,
        ];

        return Fragment::searchQuery($searchQuery)
            ->load(['video'])
            ->highlight('text', $highlightOptions)
            ->highlight('text.fallback', $highlightOptions)
            ->highlight('text.ngram', $highlightOptions)
            ->paginate($perPage, 'page', $page);
    }

    /**
     * Поиск фрагментов для экспорта (без пагинации)
     */
    public function searchForExport(string $query, ?int $playlistId = null, ?int $videoId = null, bool $matchPhrase = false, int $limit = 1000): Paginator
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

        // Используем такой же запрос как в search, но с большим лимитом и без пагинации
        return Fragment::searchQuery($searchQuery)
            ->load(['video'])
            ->paginate($limit, 'page', 1);
    }
}
