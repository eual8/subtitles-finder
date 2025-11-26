<?php

namespace App\Filament\Pages;

use App\Data\FragmentSearchResult;
use App\Models\Playlist;
use App\Models\Video;
use App\Services\FragmentSearchService;
use App\Services\VideoService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Search extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.pages.search';

    protected FragmentSearchService $searchService;

    protected VideoService $videoService;

    #[Url]
    public string $searchQuery = '';

    #[Url]
    public int $page = 1;

    #[Url]
    public ?int $videoId = null;

    #[Url]
    public ?int $playlistId = null;

    #[Url]
    public bool $matchPhrase = false;

    public ?array $data = [];

    // Добавляем метод для формы
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('searchQuery')
                    ->label('Поисковый запрос')
                    ->required(),

                Select::make('playlistId')
                    ->label('Плейлист')
                    ->options(function () {
                        return Playlist::orderBy('title')->pluck('title', 'id');
                    })
                    ->placeholder('Все плейлисты')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->playlistId = $state;
                        $this->filterPlaylist();
                    })
                    ->searchable()
                    ->nullable(),

                Select::make('videoId')
                    ->label('Видео')
                    ->options(function () {
                        return $this->playlistId
                            ? $this->getVideos()
                            : Video::orderBy('title')->pluck('title', 'id');
                    })
                    ->placeholder('Все видео')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->videoId = $state;
                        $this->search();
                    })
                    ->searchable()
                    ->nullable()
                    ->visible(fn () => $this->playlistId !== null),

                Checkbox::make('matchPhrase')
                    ->label('Точное соответствие фразе')
                    ->live()
                    ->afterStateUpdated(fn () => $this->search()),
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('admin.search.index'), 403);

        // Инициализация данных формы
        $this->form->fill([
            'searchQuery' => $this->searchQuery,
            'playlistId' => $this->playlistId,
            'videoId' => $this->videoId,
            'matchPhrase' => $this->matchPhrase,
        ]);
    }

    // Обновление свойств из данных формы
    protected function updateFromForm(): void
    {
        $data = $this->form->getState();

        $this->searchQuery = $data['searchQuery'] ?? '';
        $this->playlistId = $data['playlistId'] ?? null;
        $this->videoId = $data['videoId'] ?? null;
        $this->matchPhrase = $data['matchPhrase'] ?? false;
    }

    public function search(): void
    {
        $this->updateFromForm();
        $this->page = 1;
    }

    public function filterPlaylist(): void
    {
        $this->videoId = null;
        $this->page = 1;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('admin.search.index');
    }

    public function boot(FragmentSearchService $searchService, VideoService $videoService): void
    {
        $this->searchService = $searchService;
        $this->videoService = $videoService;
    }

    protected function searchFragments(): FragmentSearchResult
    {
        return $this->searchService->search(
            query: $this->searchQuery,
            playlistId: $this->playlistId,
            videoId: $this->videoId,
            page: $this->page,
            matchPhrase: $this->matchPhrase,
            withPreparedHighlights: true
        );
    }

    public function gotoPage($pageNumber)
    {
        $this->page = $pageNumber;
    }

    protected function getVideos(): Collection
    {
        if ($this->playlistId === null) {
            return collect();
        }

        return $this->videoService->getVideosForSelect($this->playlistId);
    }

    protected function getViewData(): array
    {
        $searchResult = $this->searchFragments();

        return [
            'fragments' => $searchResult->paginator,
            'results' => $searchResult->preparedHits,
            'videos' => $this->playlistId !== null ? $this->getVideos() : collect(),
            'playlists' => Playlist::orderBy('title')->get()->pluck('title', 'id'),
        ];
    }

    public function nextPage()
    {
        $this->page += 1;
        $this->searchFragments();
    }

    public function previousPage()
    {
        $this->page -= 1;

        if ($this->page < 1) {
            $this->page = 1;
        }

        $this->searchFragments();
    }

    /**
     * Экспортирует все найденные результаты в текстовый файл
     */
    public function exportResults(): StreamedResponse
    {
        $this->updateFromForm();

        // Получаем все результаты для экспорта (без пагинации)
        $results = $this->searchService->searchForExport(
            query: $this->searchQuery,
            playlistId: $this->playlistId,
            videoId: $this->videoId,
            matchPhrase: $this->matchPhrase
        );

        // Санитизируем имя файла
        $filename = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $this->searchQuery);
        $filename = $filename ?: 'search_results';
        $filename .= '.txt';

        return response()->streamDownload(function () use ($results) {
            // Количество фрагментов до и после найденного для контекста
            $contextFragmentsCount = 5;

            // Создаем набор для отслеживания уникальных ID фрагментов
            $processedFragmentIds = [];

            foreach ($results as $index => $hit) {
                $fragment = $hit->model();
                if (! $fragment) {
                    continue;
                }

                $videoTitle = $fragment->video->title ?? 'Без названия';

                echo '------- Фрагмент #'.($index + 1)." из Видео: {$videoTitle} -------\n";

                // Получаем предыдущие фрагменты
                $previousFragments = \App\Models\Fragment::where('video_id', $fragment->video_id)
                    ->where('id', '<', $fragment->id)
                    ->orderBy('id', 'desc')
                    ->limit($contextFragmentsCount)
                    ->get()
                    ->reverse();

                // Выводим предыдущие фрагменты
                if ($previousFragments->count() > 0) {
                    foreach ($previousFragments as $prevFragment) {
                        if (! in_array($prevFragment->id, $processedFragmentIds)) {
                            echo "{$prevFragment->text}\n";
                            $processedFragmentIds[] = $prevFragment->id;
                        }
                    }
                }

                // Выводим основной найденный фрагмент
                if (! in_array($fragment->id, $processedFragmentIds)) {
                    echo "{$fragment->text}\n";
                    $processedFragmentIds[] = $fragment->id;
                }

                // Получаем следующие фрагменты
                $nextFragments = \App\Models\Fragment::where('video_id', $fragment->video_id)
                    ->where('id', '>', $fragment->id)
                    ->orderBy('id', 'asc')
                    ->limit($contextFragmentsCount)
                    ->get();

                // Выводим следующие фрагменты
                if ($nextFragments->count() > 0) {
                    foreach ($nextFragments as $nextFragment) {
                        if (! in_array($nextFragment->id, $processedFragmentIds)) {
                            echo "{$nextFragment->text}\n";
                            $processedFragmentIds[] = $nextFragment->id;
                        }
                    }
                }

                echo "\n\n";
            }
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename={$filename}",
            'charset' => 'UTF-8',
        ]);
    }
}
