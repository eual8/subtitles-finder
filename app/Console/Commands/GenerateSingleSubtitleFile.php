<?php

namespace App\Console\Commands;

use App\Models\Fragment;
use App\Models\Playlist;
use App\Models\Video;
use App\Services\YoutubeService;
use Illuminate\Console\Command;

class GenerateSingleSubtitleFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-single-subtitle-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(YoutubeService $youtubeService)
    {
        $playlistTitle = $this->choice('Which playlist should synchronize?',
            Playlist::all()->pluck('title', 'id')->toArray()
        );

        $playlist = Playlist::where('title', $playlistTitle)->first();

        $videos = Video::where('playlist_id', $playlist->id)->get();

        $progressBar = $this->output->createProgressBar(count($videos));
        $progressBar->start();

        $outputPath = storage_path('app/public/all_subtitles.txt');
        file_put_contents($outputPath, '');

        foreach ($videos as $video) {
            $progressBar->advance();

            $content = "\n\n``` Передача \"{$video->title}\": ```\n\n";
            file_put_contents($outputPath, $content, FILE_APPEND);

            $fragments = Fragment::where('video_id', $video->id)->orderBy('id', 'ASC')->get();
            if ($fragments->isEmpty()) {
                continue;
            }

            foreach ($fragments as $fragment) {
                file_put_contents($outputPath, $fragment->text."\n", FILE_APPEND);
            }
        }
        $progressBar->finish();

        return self::SUCCESS;
    }
}
