<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use App\Models\Fragment;
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

    protected $searchResult;

    public function mount(): void
    {
        $this->searchFragments();
    }

    public function search()
    {
        $this->page = 1;
        $this->searchFragments();
    }

    protected function searchFragments()
    {
        $query = Query::match()
            ->field('text')
            ->query($this->searchQuery);

        $this->searchResult = Fragment::searchQuery($query)
            ->load(['video'])
            ->highlight('text', [
                'pre_tags' => ['<mark><b>'],
                'post_tags' => ['</b></mark>'],
            ])->paginate(10, 'page', $this->page);
    }

    public function gotoPage($pageNumber)
    {
        $this->page = $pageNumber;
        $this->searchFragments();
    }

    protected function getViewData(): array
    {
        return [
            'fragments' => $this->searchResult,
        ];
    }
}
