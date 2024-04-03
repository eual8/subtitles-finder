<?php

namespace App\Filament\Pages;

use App\Models\Playlist;
use App\Services\FragmentSearchService;
use App\Services\VideoService;
use Elastic\ScoutDriverPlus\Paginator;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class Search extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.pages.search';

    protected FragmentSearchService $searchService;

    protected VideoService $videoService;

    #[Url]
    public string $searchQuery = '';

    #[Url]
    public int $page = 1;

    #[Url]
    public ?int $videoId = null;

    #[Url]
    public ?int $playlistId = null;

    #[Url]
    public bool $matchPhrase = false;

    public function search(): void
    {
        $this->page = 1;
    }

    public function filterPlaylist(): void
    {
        $this->dispatch('playlist-updated');

        $this->videoId = null;
        $this->page = 1;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('admin.search.index');
    }

    public function boot(FragmentSearchService $searchService, VideoService $videoService): void
    {
        $this->searchService = $searchService;
        $this->videoService = $videoService;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('admin.search.index'), 403);
    }

    protected function searchFragments(): Paginator
    {
        return $this->searchService->search(
            query: $this->searchQuery,
            playlistId: $this->playlistId,
            videoId: $this->videoId,
            page: $this->page,
            matchPharase: $this->matchPhrase
        );
    }

    public function gotoPage($pageNumber)
    {
        $this->page = $pageNumber;
    }

    protected function getVideos(): Collection
    {
        return $this->videoService->getVideosForSelect($this->playlistId);
    }

    protected function getViewData(): array
    {
        return [
            'fragments' => $this->searchFragments(),
            'videos' => $this->playlistId ? $this->getVideos() : [],
            'playlists' => Playlist::orderBy('title')->get()->pluck('title', 'id'),
        ];
    }

    public function nextPage()
    {
        $this->page += 1;
        $this->searchFragments();
    }

    public function previousPage()
    {
        $this->page -= 1;

        if ($this->page < 1) {
            $this->page = 1;
        }

        $this->searchFragments();
    }
}
