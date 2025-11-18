<?php

namespace App\Filament\Pages;

use App\Models\Playlist;
use App\Models\Video;
use App\Services\TypesenseSearchService;
use App\Services\VideoService;
use App\Support\TypesenseSearchResult;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class VectorSearch extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationLabel = 'Vector Search';

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.vector-search';

    protected TypesenseSearchService $searchService;

    protected VideoService $videoService;

    #[Url]
    public string $searchQuery = '';

    #[Url]
    public int $page = 1;

    #[Url]
    public ?int $videoId = null;

    #[Url]
    public ?int $playlistId = null;

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
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        // abort_unless(auth()->user()->can('admin.vector-search.index'), 403);

        // Инициализация данных формы
        $this->form->fill([
            'searchQuery' => $this->searchQuery,
            'playlistId' => $this->playlistId,
            'videoId' => $this->videoId,
        ]);
    }

    // Обновление свойств из данных формы
    protected function updateFromForm(): void
    {
        $data = $this->form->getState();

        $this->searchQuery = $data['searchQuery'] ?? '';
        $this->playlistId = $data['playlistId'] ?? null;
        $this->videoId = $data['videoId'] ?? null;
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
        return auth()->check() && auth()->user()->can('admin.vector-search.index');
    }

    public function boot(TypesenseSearchService $searchService, VideoService $videoService): void
    {
        $this->searchService = $searchService;
        $this->videoService = $videoService;
    }

    protected function searchFragments(): ?TypesenseSearchResult
    {
        if (empty($this->searchQuery)) {
            return null;
        }

        try {
            return $this->searchService->search(
                query: $this->searchQuery,
                playlistId: $this->playlistId,
                videoId: $this->videoId,
                page: $this->page
            );
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Ошибка поиска')
                ->body('Не удалось выполнить векторный поиск: '.$exception->getMessage())
                ->send();

            report($exception);

            return null;
        }
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
        return [
            'fragments' => $this->searchFragments(),
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
}
