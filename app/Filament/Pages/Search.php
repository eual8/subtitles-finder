<?php

namespace App\Filament\Pages;

use App\Models\Playlist;
use App\Models\Video;
use App\Services\FragmentSearchService;
use Elastic\ScoutDriverPlus\Paginator;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class Search extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.pages.search';

    protected FragmentSearchService $searchService;

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
        $this->videoId = null;
        $this->page = 1;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('admin.search.index');
    }

    public function boot(FragmentSearchService $searchService): void
    {
        $this->searchService = $searchService;
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
