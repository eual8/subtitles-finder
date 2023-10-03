<?php

namespace App\Services;

use Storage;
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

    public function getSubtitles(string $youtubeId, string $langCode = 'ru'): ?string
    {
        $filePath = Storage::disk('public')->path('');

        $options = [
            'yt-dlp',
            '--write-sub',
            '--sub-lang',
            $langCode,
            '--no-write-auto-subs',
            '--skip-download',
            '--no-overwrites',
            '--output',
            $filePath.'%(id)s.%(ext)s',
            $youtubeId,
        ];

        $process = new Process($options);
        $process->setTimeout(60 * 2); // 2 minutes
        $process->run();

        if (! $process->isSuccessful()) {
            \Log::info('Yt-dlp - subtitles download', [$process->getErrorOutput()]);

            return null;
        }

        if (str_contains($process->getOutput(), 'There are no subtitles')) {
            return null;
        }

        return file_get_contents($filePath.$youtubeId.'.'.$langCode.'.vtt');
    }

    public function downloadAudio(string $youtubeId, string $format = 'wav'): string
    {
        $filePath = Storage::disk('public')->path('').$youtubeId.'.'.$format;

        $options = [
            'yt-dlp',
            '--extract-audio',
            '--no-overwrites',
            '--audio-format',
            $format,
            'https://www.youtube.com/watch?v='.$youtubeId,
            '--postprocessor-args',
            '-ar 16000',  // Важное требование к аудио 16 KHz для работы whisper
            '--output',
            $filePath,
        ];

        $process = new Process($options);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $filePath;
    }
}
