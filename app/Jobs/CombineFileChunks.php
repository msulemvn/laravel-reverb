<?php

namespace App\Jobs;

use App\Events\FileEvent;
use App\Events\FileProcessingProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CombineFileChunks implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $fileIdentifier,
        public string $originalFilename,
        public int $totalChunks,
        public int $userId
    ) {}

    public function handle(): void
    {
        try {
            // Step 1: Validate chunks (10%)
            broadcast(new FileProcessingProgress(
                10,
                'processing',
                'Validating uploaded chunks...',
                $this->userId
            ));

            $chunkDir = storage_path("app/temp/chunks/{$this->fileIdentifier}");

            if (!File::exists($chunkDir)) {
                throw new \Exception("Chunk directory not found: {$this->fileIdentifier}");
            }

            $chunks = [];
            for ($i = 0; $i < $this->totalChunks; $i++) {
                $chunkPath = "{$chunkDir}/chunk_{$i}";
                if (!File::exists($chunkPath)) {
                    throw new \Exception("Missing chunk {$i} for upload {$this->fileIdentifier}");
                }
                $chunks[] = $chunkPath;
            }

            // Step 2: Start combining chunks (30%)
            broadcast(new FileProcessingProgress(
                30,
                'processing',
                'Combining file chunks...',
                $this->userId
            ));

            $tempFile = storage_path("app/temp/{$this->fileIdentifier}_combined");
            File::ensureDirectoryExists(dirname($tempFile));

            $output = fopen($tempFile, 'wb');
            $processedChunks = 0;

            foreach ($chunks as $chunk) {
                $input = fopen($chunk, 'rb');
                stream_copy_to_stream($input, $output);
                fclose($input);

                $processedChunks++;
                // Progress from 30% to 70% while combining
                $progress = 30 + (($processedChunks / $this->totalChunks) * 40);
                broadcast(new FileProcessingProgress(
                    (int) $progress,
                    'processing',
                    "Combining chunks... ({$processedChunks}/{$this->totalChunks})",
                    $this->userId
                ));
            }
            fclose($output);

            // Step 3: Moving to final location (80%)
            broadcast(new FileProcessingProgress(
                80,
                'processing',
                'Saving file...',
                $this->userId
            ));

            $extension = pathinfo($this->originalFilename, PATHINFO_EXTENSION);
            $filename = pathinfo($this->originalFilename, PATHINFO_FILENAME);
            $newFileName = $filename . '_' . time() . '.' . $extension;

            $finalPath = storage_path("app/public/pictures/{$newFileName}");
            File::ensureDirectoryExists(dirname($finalPath));
            File::move($tempFile, $finalPath);

            // Step 4: Cleanup (90%)
            broadcast(new FileProcessingProgress(
                90,
                'processing',
                'Cleaning up...',
                $this->userId
            ));

            File::deleteDirectory($chunkDir);

            // Step 5: Complete (100%)
            broadcast(new FileProcessingProgress(
                100,
                'completed',
                'File processed successfully!',
                $this->userId
            ));

            $fileUrl = Storage::url('pictures/' . $newFileName);

            broadcast(new FileEvent($newFileName, $fileUrl, $this->userId, 'Your file has been processed successfully!'));
        } catch (\Exception $e) {
            broadcast(new FileProcessingProgress(
                0,
                'failed',
                'Processing failed: ' . $e->getMessage(),
                $this->userId
            ));
            throw $e;
        }
    }
}
