<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\YoutubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportPlaylistSubtitleTexts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-playlist-texts {playlistId : YouTube playlist ID} {--lang=ru : Subtitle language code} {--overwrite : Overwrite existing text files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download VTT subtitles for a playlist and export plain text files';

    /**
     * Execute the console command.
     */
    public function handle(YoutubeService $youtubeService)
    {
        $playlistId = (string) $this->argument('playlistId');
        $language = (string) $this->option('lang');

        $videos = Video::where('playlist_id', $playlistId)->get();

        if (empty($videos)) {
            $this->error('No videos found for the provided playlist ID.');

            return self::FAILURE;
        }

        $outputDir = storage_path('public/text');

        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0775, true);
        }

        $progressBar = $this->output->createProgressBar(count($videos));
        $progressBar->start();

        $exported = 0;
        $skipped = 0;

        foreach ($videos as $video) {
            $progressBar->advance();

            $subtitles = $video->subtitles;

            if (empty($subtitles)) {
                $skipped++;

                continue;
            }

            $plainText = $this->convertVttToText($subtitles);

            if ($plainText === '') {
                $skipped++;

                continue;
            }

            $baseName = $this->sanitizeFilename($video['title']);
            if ($baseName === '') {
                $baseName = $video['id'];
            }

            $fileName = $baseName.'.txt';
            $filePath = $outputDir.DIRECTORY_SEPARATOR.$fileName;

            if (! $this->option('overwrite') && File::exists($filePath)) {
                $fileName = $baseName.'-'.$video['id'].'.txt';
                $filePath = $outputDir.DIRECTORY_SEPARATOR.$fileName;
            }

            File::put($filePath, $plainText);
            $exported++;
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Exported {$exported} text file(s) to storage/public/text");

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} video(s) without subtitles or with empty text.");
        }

        return Command::SUCCESS;
    }

    private function convertVttToText(string $vtt): string
    {
        $vtt = str_replace(["\r\n", "\r"], "\n", $vtt);
        $vtt = preg_replace('/^\xEF\xBB\xBF/', '', $vtt);

        $lines = explode("\n", $vtt);
        $output = [];
        $previousLine = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^(WEBVTT|Kind:|Language:|NOTE|STYLE|REGION)/iu', $line)) {
                continue;
            }

            if (str_contains($line, '-->')) {
                continue;
            }

            $line = preg_replace('/<[^>]+>/', '', $line);
            $line = html_entity_decode($line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $line = preg_replace('/\s+/u', ' ', $line);
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($previousLine !== null) {
                if ($line === $previousLine) {
                    continue;
                }

                if (str_contains($previousLine, $line)) {
                    continue;
                }

                if (str_contains($line, $previousLine)) {
                    $output[count($output) - 1] = $line;
                    $previousLine = $line;

                    continue;
                }
            }

            $output[] = $line;
            $previousLine = $line;
        }

        if (empty($output)) {
            return '';
        }

        return implode("\n", $output)."\n";
    }

    private function sanitizeFilename(string $title): string
    {
        $title = trim($title);

        if ($title === '') {
            return '';
        }

        $title = preg_replace('/[\x00-\x1F\x7F\/\\\\:\*\?"<>|]+/u', ' ', $title);
        $title = preg_replace('/\s+/u', ' ', $title);
        $title = rtrim($title, '. ');
        $title = Str::limit($title, 180, '');

        return trim($title);
    }
}
