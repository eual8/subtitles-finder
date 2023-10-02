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

    public array $fragments;

    public function mount(): void
    {
        $query = Query::match()
            ->field('text')
            ->query($this->searchQuery);

        $searchResult = Fragment::searchQuery($query)
            ->load(['video'])
            ->highlight('text', [
                'pre_tags' => ['<mark><b>'],
                'post_tags' => ['</b></mark>'],
            ])->execute();

        $this->fragments = $searchResult->hits()->toArray();
    }
}
