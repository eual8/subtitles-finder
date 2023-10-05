<?php

namespace App\Console\Commands;

use App\Models\Fragment;
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
        // Remove memory limit
        ini_set('memory_limit', '-1');

        $count = Video::count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        // Remove all records from Elasticsearch index
        $this->call('scout:flush', ['model' => 'App\Models\Fragment']);

        // Remove all fragment records from DB
        Fragment::truncate();
        foreach (Video::orderBy('id')->lazy() as $video) {
            $fragmentService->createFragments($video);
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
