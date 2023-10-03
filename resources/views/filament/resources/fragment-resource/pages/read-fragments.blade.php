<x-filament-panels::page>
    @include('filament.read_buttons')

    @foreach($fragments as $fragment)
        <p>{{ $fragment->text }}</p>
    @endforeach

    @include('filament.read_buttons')
</x-filament-panels::page>
