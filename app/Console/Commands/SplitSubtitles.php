<?php

namespace App\Console\Commands;

use App\Models\Playlist;
use App\Models\Video;
use App\Services\FragmentService;
use Illuminate\Console\Command;

class SplitSubtitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:split-subtitles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to split all subtitles into fragments';

    /**
     * Execute the console command.
     */
    public function handle(FragmentService $fragmentService)
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

        foreach ($videos as $video) {
            $progressBar->advance();

            $fragmentService->deleteFragments($video);
            $fragmentService->createFragments($video);
        }

        $progressBar->finish();
    }
}
