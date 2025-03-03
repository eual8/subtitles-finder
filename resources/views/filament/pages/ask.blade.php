<x-filament-panels::page>
    <form wire:submit="search" class="container">

        <input type="text" wire:model="searchQuery" class="w-1/2">

        <button type="submit" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold py-2 px-4 border border-blue-500 hover:border-transparent rounded">
            Search
        </button>

        <div class="w-full flex gap-1">
            <select wire:model="playlistId" wire:change="filterPlaylist" class="mt-2 w-1/2">
                <option value="">All playlists</option>
                @foreach($playlists as $key => $playlist)
                    <option value="{{ $key }}">{{ $playlist }}</option>
                @endforeach
            </select>

            <select wire:model="videoId" wire:change="search" class="mt-2 w-1/2">
                <option value="">All videos</option>
                @foreach($videos as $key => $video)
                    <option value="{{ $key }}">{{ $video }}</option>
                @endforeach
            </select>
        </div>
    </form>
</x-filament-panels::page>
