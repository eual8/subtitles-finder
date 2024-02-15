<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\VideoQueue;
use Illuminate\Console\Command;

class AddVideosToWhisperQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-videos-to-whisper-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add videos without subtitles to the queue to transcribe';

    /**
     * Execute the console command.
     */
    public function handle(VideoQueue $queue)
    {
        $videos = Video::whereNull('subtitles')
            ->whereNull('subtitles_autogenerated')
            ->orderBy('id')
            ->get();

        $progressBar = $this->output->createProgressBar(count($videos));
        $progressBar->start();

        foreach ($videos as $video) {

            $queue->addVideo($video);

            $progressBar->advance();
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
