<?php

namespace App\Services;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class WhisperService
{
    public function __construct(
        protected YoutubeService $youtubeService,
    ) {
    }

    public function transcribe(string $youtubeId, string $langCode = 'ru', $output = null): string
    {
        $audioFile = $this->youtubeService->downloadAudio($youtubeId);

        $options = [
            './main',
            '-bs',  // <== Нужно для того чтобы убрать галлюцинации модели когда идут сплошные дубли текста
            '5',    // https://github.com/ggerganov/whisper.cpp/issues/896#issuecomment-1569586018
            '-et',
            '2.8',
            '-mc',
            '64',   // <==
            '-l',
            $langCode,
            '--output-vtt',
            '-m',
            config('whisper.whisper_model_path'),
            '-f',
            $audioFile,
        ];

        $process = new Process($options);
        $process->setTimeout(60 * 60 * 3); // 3 hours
        $process->setWorkingDirectory(config('whisper.whisper_project_path'));

        if (! empty($output)) {
            $process->run(function ($type, $buffer) use ($output) {
                if ($type === \Symfony\Component\Console\Command\Command::FAILURE) {
                    $output->writeln('ERROR!!! - '.$buffer);

                    return Command::FAILURE;
                } else {
                    $output->writeln($buffer);
                }
            });
        } else {
            $process->run();
        }

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Delete audio file
        unlink($audioFile);

        return file_get_contents($audioFile.'.vtt');
    }
}
