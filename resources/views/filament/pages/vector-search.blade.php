<x-filament-panels::page>

    <form wire:submit.prevent="search">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Поиск
        </x-filament::button>
    </form>

    @if($fragments && $fragments->hits()->count() > 0)
        <div class="mt-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Найдено результатов: {{ $fragments->total() }}
            </p>

            @foreach($fragments->hits() as $hit)
                <a href="/admin/fragments/{{ $hit->model()?->id }}/read" target="_blank">
                    <div class="flex items-center border-b border-gray-300 dark:border-gray-700 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <div class="block">
                            <img src="{{ $hit->model()?->video_image }}" title="{{ $hit->model()?->video->title }}"
                                 style="max-width: 120px" class="object-cover block p-2">
                        </div>

                        <div class="block flex-1">
                            <span class="text-gray-900 dark:text-gray-100">{{ $hit->model()?->text }}</span>
                        </div>
                    </div>
                </a>
            @endforeach

            @if($fragments->hasPages())
                <div class="mt-4 flex justify-center gap-2">
                    @if(!$fragments->onFirstPage())
                        <x-filament::button wire:click="previousPage" size="sm" outlined>
                            Предыдущая
                        </x-filament::button>
                    @endif

                    <span class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                        Страница {{ $fragments->currentPage() }} из {{ $fragments->lastPage() }}
                    </span>

                    @if($fragments->hasMorePages())
                        <x-filament::button wire:click="nextPage" size="sm" outlined>
                            Следующая
                        </x-filament::button>
                    @endif
                </div>
            @endif
        </div>
    @elseif($fragments && $fragments->isEmpty())
        <div class="mt-6 text-center text-gray-500 dark:text-gray-400">
            Результаты не найдены
        </div>
    @endif

</x-filament-panels::page>
