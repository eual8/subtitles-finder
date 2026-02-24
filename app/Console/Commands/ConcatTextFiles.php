<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConcatTextFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:concat-text-files {--count=15 : Number of merged files to create} {--output-dir= : Output directory for merged files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Concatenate text files from storage/public/text into a fixed number of merged files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetCount = (int) $this->option('count');
        if ($targetCount < 1) {
            $this->error('Option --count must be at least 1.');

            return Command::FAILURE;
        }

        $inputDir = storage_path('public/text');
        if (! File::exists($inputDir)) {
            $this->error("Input directory not found: {$inputDir}");

            return Command::FAILURE;
        }

        $files = collect(File::files($inputDir))
            ->filter(static fn ($file) => strtolower($file->getExtension()) === 'txt')
            ->sortBy(static fn ($file) => $file->getFilename(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $total = $files->count();
        if ($total === 0) {
            $this->error('No .txt files found to merge.');

            return Command::FAILURE;
        }

        if ($total < $targetCount) {
            $this->error("Not enough files to create {$targetCount} merged files (found {$total}).");

            return Command::FAILURE;
        }

        $outputDir = (string) $this->option('output-dir');
        if ($outputDir === '') {
            $outputDir = $inputDir.DIRECTORY_SEPARATOR.'merged';
        }

        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0775, true);
        }

        $baseSize = intdiv($total, $targetCount);
        $remainder = $total % $targetCount;
        $startIndex = 0;

        for ($groupIndex = 0; $groupIndex < $targetCount; $groupIndex++) {
            $groupSize = $baseSize + ($groupIndex < $remainder ? 1 : 0);
            $groupFiles = $files->slice($startIndex, $groupSize);
            $startIndex += $groupSize;

            $contents = '';
            $nameParts = [];

            foreach ($groupFiles as $file) {
                $nameParts[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $contents .= File::get($file->getPathname());
            }

            $outputName = implode('-', $nameParts).'.txt';
            $outputPath = $outputDir.DIRECTORY_SEPARATOR.$outputName;

            File::put($outputPath, $contents);
        }

        $this->info("Created {$targetCount} merged file(s) in {$outputDir}");

        return Command::SUCCESS;
    }
}
