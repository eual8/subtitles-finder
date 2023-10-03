<?php

namespace App\Console\Commands;

use App\Models\Playlist;
use App\Models\Video;
use App\Services\YoutubeService;
use Illuminate\Console\Command;

class SyncPlaylistSubtitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-playlist-subtitles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync subtitles from youtube in all videos of playlist';

    /**
     * Execute the console command.
     */
    public function handle(YoutubeService $youtubeService)
    {
        $playlistTitle = $this->choice('Which playlist should synchronize?',
            Playlist::all()->pluck('title', 'id')->toArray()
        );

        $playlist = Playlist::where('title', $playlistTitle)->first();

        $videos = Video::where('playlist_id', $playlist->id)
            ->whereNull('subtitles')
            ->orderBy('id')
            ->get();

        $progressBar = $this->output->createProgressBar(count($videos));
        $progressBar->start();

        foreach ($videos as $video) {
            $progressBar->advance();

            $subtitles = $youtubeService->getSubtitles($video->youtube_id);

            if ($subtitles) {
                $video->update(['subtitles' => $subtitles]);
                $this->info('Saved subtitles for video - '.$video->title);
            }
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
