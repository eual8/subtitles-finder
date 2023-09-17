<?php

namespace App\Console\Commands;

use App\Models\Playlist;
use App\Models\Video;
use App\Services\YoutubeService;
use Illuminate\Console\Command;
use Log;
use Storage;

class SyncPlaylistVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-playlist-videos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync videos from youtube playlist';

    /**
     * Execute the console command.
     */
    public function handle(YoutubeService $youtubeService)
    {
        $playlistTitle = $this->choice('Which playlist should synchronize?',
            Playlist::all()->pluck('title', 'id')->toArray()
        );

        $playlist = Playlist::where('title', $playlistTitle)->first();

        $videos = $youtubeService->getVideos($playlist->youtube_id);

        $progressBar = $this->output->createProgressBar(count($videos));
        $progressBar->start();

        $newVideos = [];
        foreach ($videos as $videoData) {
            $progressBar->advance();

            if (Video::where('youtube_id', $videoData['id'])->exists()) {
                continue;
            }

            $imageName = $videoData['id'].'.jpg';
            Storage::disk('public')->put($imageName, $this->fileGetContentsCurl($youtubeService->getThumbUrl($videoData['id'])));

            Video::create([
                'youtube_id' => $videoData['id'],
                'title' => $videoData['title'],
                'playlist_id' => $playlist->id,
                'is_enabled' => true,
                'duration' => $videoData['duration'],
                'attachments' => $imageName,
                //                'subtitles' => $youtubeService->getSubtitles($youtubeId),
            ]);

            $newVideos[] = $videoData['id'];
        }

        $progressBar->finish();

        $this->info('Saved new videos - '.count($newVideos));
    }

    public function fileGetContentsCurl(string $url)
    {
        Log::info('File get content from url: '.$url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
