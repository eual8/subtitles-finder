<?php

namespace App\Services;

use App\Models\Fragment;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;

final class FragmentSearchService
{
    public function search(string $query, ?int $playlistId, ?int $videoId, int $page, int $perPage = 20, bool $matchPharase = false): Paginator
    {

        if ($matchPharase === true) {
            $searchFunctionName = 'matchPhrasePrefix';
        } else {
            $searchFunctionName = 'match';
        }

        if ($playlistId === null) {
            // Фильтров нет
            $searchQuery = Query::{$searchFunctionName}()
                ->field('text')
                ->query($query);
        } else {
            // Фильтруем по Плейлисту
            $playlistFilter = Query::term()
                ->field('playlist_id')
                ->value($playlistId);

            $must = Query::{$searchFunctionName}()
                ->field('text')
                ->query($query);

            $searchQuery = Query::bool()
                ->must($must)
                ->must($playlistFilter);

            // Фильтруем по Плейлисту и по Видео
            if ($videoId !== null) {
                $searchQuery->must(Query::term()
                    ->field('video_id')
                    ->value($videoId));
            }
        }

        // TODO: refactor to OOP
        if ($matchPharase === true) {
            $searchQuery->maxExpansions(50)->slop(5);
        }

        return Fragment::searchQuery($searchQuery)
            ->load(['video'])
            ->highlight('text', [
                'pre_tags' => ['<mark><b>'],
                'post_tags' => ['</b></mark>'],
            ])->paginate($perPage, 'page', $page);
    }
}
