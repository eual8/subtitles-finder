<?php

namespace App\Console\Commands;

use App\Models\Fragment;
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
            ->lazy();

        $count = Video::where('playlist_id', $playlist->id)->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        // Remove all records from Elasticsearch index
        $this->call('scout:flush', ['model' => 'App\Models\Fragment']);

        // Remove all fragment records from DB
        Fragment::truncate();
        foreach ($videos as $video) {
            $progressBar->advance();

            $fragmentService->createFragments($video);
        }

        $progressBar->finish();
    }
}
