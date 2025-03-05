<x-filament-panels::page>

    <form wire:submit.prevent="search">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Поиск
        </x-filament::button>
    </form>

    @if($fragments->hits()->count() > 0)
        <div class="mt-4">
            <x-filament::button wire:click="exportResults" color="success" icon="heroicon-o-arrow-down-tray" tooltip="Export">
            </x-filament::button>
        </div>
    @endif

    @foreach($fragments->hits() as $hit)
        <a href="/admin/fragments/{{ $hit->model()?->id }}/read" target="_blank">
            <div class="flex items-center border-b border-gray-300 py-2">
                <div class="block">
                    <img src="{{ $hit->model()?->video_image }}" title="{{ $hit->model()?->video->title }}"
                         style="max-width: 120px" class="object-cover block p-2">
                </div>

                <div class="block">
                    @foreach($hit->highlight()->snippets('text') as $highlight)
                        <span class="">{!! $highlight !!}</span>
                    @endforeach
                </div>
            </div>
        </a>
    @endforeach

    <x-filament::pagination :paginator="$fragments" />

</x-filament-panels::page>
