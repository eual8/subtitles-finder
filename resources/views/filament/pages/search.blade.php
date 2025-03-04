<x-filament-panels::page>

    {{ $this->form }}
    
    <x-filament::button wire:click="search" class="mt-4">
        Поиск
    </x-filament::button>

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
