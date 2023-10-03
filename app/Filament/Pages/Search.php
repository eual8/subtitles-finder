<?php

namespace App\Filament\Pages;

use App\Models\Fragment;
use App\Models\Video;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;
use Filament\Pages\Page;
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

    public function search()
    {
        $this->page = 1;
    }

    protected function searchFragments(): Paginator
    {
        if (! empty($this->videoId)) {
            $filter = Query::term()
                ->field('video_id')
                ->value($this->videoId);

            $must = Query::match()
                ->field('text')
                ->query($this->searchQuery);

            $query = Query::bool()
                ->must($must)
                ->must($filter);
        } else {
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

    protected function getViewData(): array
    {
        return [
            'fragments' => $this->searchFragments(),
            'videos' => Video::orderBy('title')
                ->get()
                ->pluck('title', 'id'),
        ];
    }
}
