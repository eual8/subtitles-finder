<?php

namespace App\Filament\Pages;

use App\Models\Fragment;
use App\Models\Playlist;
use App\Models\Video;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class Search extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.pages.search';

    #[Url]
    public string $searchQuery = '';

    #[Url]
    public int $page = 1;

    #[Url]
    public int $videoId = 0;

    #[Url]
    public int $playlistId = 0;

    public function search(): void
    {
        $this->page = 1;
    }

    public function filterPlaylist(): void
    {
        $this->videoId = 0;
        $this->page = 1;
    }

    protected function searchFragments(): Paginator
    {
        // Фильтруем по Плейлисту
        if (! empty($this->playlistId)) {

            $playlistFilter = Query::term()
                ->field('playlist_id')
                ->value($this->playlistId);

            $must = Query::match()
                ->field('text')
                ->query($this->searchQuery);

            $query = Query::bool()
                ->must($must)
                ->must($playlistFilter);

            // Фильтруем по Плейлисту и по Видео
            if (! empty($this->videoId)) {
                $query->must(Query::term()
                    ->field('video_id')
                    ->value($this->videoId));
            }
        } else {
            // Фильтров нет
            $query = Query::match()
                ->field('text')
                ->query($this->searchQuery);
        }

        return Fragment::searchQuery($query)
            ->load(['video'])
            ->highlight('text', [
                'pre_tags' => ['<mark><b>'],
                'post_tags' => ['</b></mark>'],
            ])->paginate(20, 'page', $this->page);
    }

    public function gotoPage($pageNumber)
    {
        $this->page = $pageNumber;
    }

    protected function getVideos(): Collection
    {
        return Video::where('playlist_id', $this->playlistId)
            ->orderBy('title')
            ->get()
            ->pluck('title', 'id');
    }

    protected function getViewData(): array
    {
        return [
            'fragments' => $this->searchFragments(),
            'videos' => $this->getVideos(),
            'playlists' => Playlist::orderBy('title')->get()->pluck('title', 'id'),
        ];
    }
}
