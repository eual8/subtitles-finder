<x-filament-panels::page>

    <form wire:submit="search" class="container">

        <input type="text" wire:model="searchQuery" class="w-1/2">

        <button type="submit" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold py-2 px-4 border border-blue-500 hover:border-transparent rounded">
            Search
        </button>

        <div class="w-full flex">
            <select wire:model="playlistId" wire:change="filterPlaylist" class="mt-2 w-1/2">
                <option value=0>All playlists</option>
                @foreach($playlists as $key => $playlist)
                    <option value="{{ $key }}">{{ $playlist }}</option>
                @endforeach
            </select>

            <select wire:model="videoId" wire:change="search" class="mt-2 w-1/2">
                <option value=0>All videos</option>
                @foreach($videos as $key => $video)
                    <option value="{{ $key }}">{{ $video }}</option>
                @endforeach
            </select>

        </div>


    </form>

    @foreach($fragments->hits() as $hit)

        <a href="/admin/fragments/{{ $hit->model()->id }}/read" target="_blank">
            <div class="flex items-center border-b border-gray-300 py-2">
                <div class="block">
                    <img src="{{ $hit->model()->video_image }}" title="{{ $hit->model()->video->title }}"
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
