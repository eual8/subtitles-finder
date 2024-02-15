<?php

namespace App\Console\Commands;

use App\Services\VideoQueue;
use Illuminate\Console\Command;

class TranscribeVideosFromQueue extends Command
{
    /**
     * The name and signature of the console command. receive
     *
     * @var string
     */
    protected $signature = 'app:transcribe-videos-from-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(VideoQueue $queue)
    {
        $queue->transcribeQueueVideos($this, $this->output);

        return Command::SUCCESS;
    }
}
