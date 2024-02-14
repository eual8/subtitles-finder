<?php

namespace App\Console\Commands;

use App\Services\WhisperQueue;
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
    public function handle(WhisperQueue $queue)
    {
        $queue->transcribeQueueVideos($this);

        return Command::SUCCESS;
    }
}
