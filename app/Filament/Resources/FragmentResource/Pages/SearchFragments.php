<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use App\Models\Fragment;
use App\Models\Video;
use Elastic\ScoutDriverPlus\Paginator;
use Elastic\ScoutDriverPlus\Support\Query;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Url;

class SearchFragments extends Page
{
    protected static string $resource = FragmentResource::class;

    protected static string $view = 'filament.resources.fragment-resource.pages.search-fragments';

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
