<?php

namespace App\Services;

use App\Models\Fragment;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;

final class FragmentSearchService
{
    public function search(string $query, ?int $playlistId, ?int $videoId, int $page, int $perPage = 20, bool $matchPhrase = false): Paginator
    {
        if ($matchPhrase === true) {
            $mustSearchBlock = Query::matchPhrasePrefix()
                ->maxExpansions(50)
                ->slop(5);
        } else {
            $mustSearchBlock = Query::match();
        }

        $mustSearchBlock->field('text')
            ->query($query);

        $searchQuery = Query::bool()
            ->must($mustSearchBlock);

        if ($playlistId !== null) {
            $searchQuery->must(Query::term()
                ->field('playlist_id')
                ->value($playlistId));
        }

        if ($videoId !== null) {
            $searchQuery->must(Query::term()
                ->field('video_id')
                ->value($videoId));
        }

        return Fragment::searchQuery($searchQuery)
            ->load(['video'])
            ->highlight('text', [
                'pre_tags' => ['<mark><b>'],
                'post_tags' => ['</b></mark>'],
            ])->paginate($perPage, 'page', $page);
    }

    /**
     * Поиск фрагментов для экспорта (без пагинации)
     */
    public function searchForExport(string $query, ?int $playlistId = null, ?int $videoId = null, bool $matchPhrase = false, int $limit = 1000): Paginator
    {
        if ($matchPhrase === true) {
            $mustSearchBlock = Query::matchPhrasePrefix()
                ->maxExpansions(50)
                ->slop(5);
        } else {
            $mustSearchBlock = Query::match();
        }

        $mustSearchBlock->field('text')
            ->query($query);

        $searchQuery = Query::bool()
            ->must($mustSearchBlock);

        if ($playlistId !== null) {
            $searchQuery->must(Query::term()
                ->field('playlist_id')
                ->value($playlistId));
        }

        if ($videoId !== null) {
            $searchQuery->must(Query::term()
                ->field('video_id')
                ->value($videoId));
        }

        // Используем такой же запрос как в search, но с большим лимитом и без пагинации
        return Fragment::searchQuery($searchQuery)
            ->load(['video'])
            ->paginate($limit, 'page');
    }
}
