<x-filament-panels::page>

    <form wire:submit.prevent="search">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Поиск
        </x-filament::button>
    </form>

    @if(($results ?? collect())->count() > 0)
        <div class="mt-4">
            <x-filament::button wire:click="exportResults" outlined size="xs" color="gray" icon="heroicon-o-arrow-down-tray" tooltip="Export">
            </x-filament::button>
        </div>
    @endif

@foreach($results ?? [] as $result)
    @php $fragment = $result['model'] ?? null; @endphp
    <a href="/admin/fragments/{{ $fragment?->id }}/read" target="_blank">
        <div class="flex items-center border-b border-gray-300 py-2">
            <div class="block">
                <img src="{{ $fragment?->video_image }}" title="{{ $fragment?->video->title }}"
                     style="max-width: 120px" class="object-cover block p-2">
            </div>

            <div class="block">
                    @foreach($result['snippets'] ?? [] as $snippet)
                        <span>{!! $snippet !!}</span>
                    @endforeach
            </div>
        </div>
    </a>
@endforeach

    <x-filament::pagination :paginator="$fragments" />

</x-filament-panels::page>
