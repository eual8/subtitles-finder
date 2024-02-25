<x-filament-panels::page>
    <div class="grid flex-1 auto-cols-fr gap-y-1">
        @include('filament.read_buttons')

        @foreach($fragments as $fragment)
            <span title="{{ $fragment->time_string }}">{{ $fragment->text }}</span>
        @endforeach

        @include('filament.read_buttons')
    </div>
</x-filament-panels::page>
