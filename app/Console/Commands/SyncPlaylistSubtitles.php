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
            ->orderBy('id')
            ->get();

        $progressBar = $this->output->createProgressBar(count($videos));
        $progressBar->start();

        $counter = 0;
        foreach ($videos as $video) {
            $progressBar->advance();

            if ($video->subtitles) {
                continue;
            }

            $subtitles = $youtubeService->getSubtitles($video->youtube_id);

            if ($subtitles) {
                $video->update(['subtitles' => $subtitles]);
                $counter++;
            }
        }

        $progressBar->finish();

        $this->info('Saved new subtitles - '.$counter);
    }
}
