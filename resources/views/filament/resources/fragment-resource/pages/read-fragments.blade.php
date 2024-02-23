<x-filament-panels::page>
    @include('filament.read_buttons')

    @foreach($fragments as $fragment)
        <span>{{ $fragment->text }}</span><br>
    @endforeach

    @include('filament.read_buttons')
</x-filament-panels::page>
