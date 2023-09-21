<?php

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class YoutubeService
{
    public function getVideos(string $playlistId): array
    {
        $options = [
            'yt-dlp',
            '--extractor-args',
            'youtube:lang=ru',
            '--dump-json',
            '--flat-playlist',
            'https://www.youtube.com/playlist?list='.$playlistId,
        ];

        $process = new Process($options);
        $process->run();

        // executes after the command finishes
        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $videos = [];

        foreach (explode(PHP_EOL, $process->getOutput()) as $item) {
            $data = json_decode($item, true, JSON_THROW_ON_ERROR);

            if (empty($data['id'])) {
                continue;
            }

            if ($data['title'] === '[Видео с ограниченным доступом]') {
                continue;
            }

            $videos[] = [
                'id' => $data['id'],
                'title' => $data['title'],
                'url' => $data['url'],
                'duration' => $data['duration'],
                'duration_string' => $data['duration_string'] ?? '',
                'playlist_index' => $data['playlist_index'],
            ];
        }

        return array_reverse($videos);
    }

    public function getThumbUrl(string $youtubeId): string
    {
        return 'https://i3.ytimg.com/vi/'.$youtubeId.'/maxresdefault.jpg';
    }
}
