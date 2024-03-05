<x-filament-panels::page>

    <form wire:submit="search" class="container">

        <input type="text" wire:model="searchQuery" class="w-1/2">

        <button type="submit" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold py-2 px-4 border border-blue-500 hover:border-transparent rounded">
            Search
        </button>

        <div class="w-full flex gap-1">
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

        <label class="inline-flex items-center cursor-pointer mt-5">
            <input wire:model="matchPhrase" wire:change="search" type="checkbox" value="0" class="sr-only peer">
            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Match phrase</span>
        </label>

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
