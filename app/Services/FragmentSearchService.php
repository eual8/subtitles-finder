<?php

namespace App\Services;

use App\Models\Fragment;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;

final class FragmentSearchService
{
    public function search(string $query, ?int $playlistId, ?int $videoId, int $page, int $perPage, bool $matchPharase): Paginator
    {

        if ($matchPharase === true) {
            $searchFunctionName = 'matchPhrase';
        } else {
            $searchFunctionName = 'match';
        }

        // Фильтруем по Плейлисту
        if (! empty($playlistId)) {

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
            if (! empty($videoId)) {
                $searchQuery->must(Query::term()
                    ->field('video_id')
                    ->value($videoId));
            }
        } else {
            // Фильтров нет
            $searchQuery = Query::{$searchFunctionName}()
                ->field('text')
                ->query($query);
        }

        return Fragment::searchQuery($searchQuery)
            ->load(['video'])
            ->highlight('text', [
                'pre_tags' => ['<mark><b>'],
                'post_tags' => ['</b></mark>'],
            ])->paginate($perPage, 'page', $page);
    }
}
