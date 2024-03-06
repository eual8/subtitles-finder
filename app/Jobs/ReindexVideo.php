<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\FragmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReindexVideo implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Video $video,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(FragmentService $service): void
    {
        $service->reindexVideo($this->video);
    }
}
