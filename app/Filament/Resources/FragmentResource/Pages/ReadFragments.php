<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use App\Models\Fragment;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ReadFragments extends Page
{
    use InteractsWithRecord;

    const PER_PAGE = 20;

    protected static string $resource = FragmentResource::class;

    protected static string $view = 'filament.resources.fragment-resource.pages.read-fragments';

    public Collection $fragments;

    public int $lastId;

    public int $prevId;

    public function mount(int|string $record)
    {
        $this->record = $this->resolveRecord($record);

        $this->fragments = Fragment::where('id', '>=', $this->record->id)
            ->where('video_id', $this->record->video_id)
            ->orderBy('id')
            ->take(self::PER_PAGE)
            ->get();

        $this->lastId = $this->fragments->last()->id + 1;

        $prev = $this->record->id - self::PER_PAGE;

        $this->prevId = $prev < 0 ? 1 : $prev;
    }

    public function getTitle(): Htmlable|string
    {
        return $this->record->video->title;
    }
}
