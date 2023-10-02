<x-filament-panels::page>
    @foreach($fragments as $fragment)

        <a href="/admin/fragments/{{ $fragment['model']['id'] }}/read" target="_blank">
            <div class="flex items-center border-b border-gray-300 py-2">
                <div class="block">
                    <img src="{{ $fragment['model']['video_image'] }}" title="{{ $fragment['model']['video']['title'] }}" style="max-width: 120px" class="object-cover block p-2">
                </div>

                <div class="block">
                    @foreach($fragment['highlight']['text'] as $highlight)
                        <span class="">{!! $highlight !!}</span>
                    @endforeach
                </div>
            </div>
        </a>
    @endforeach


</x-filament-panels::page>
