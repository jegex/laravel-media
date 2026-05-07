<?php

namespace Jegex\Media\Conversions\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Jegex\Media\Conversions\FileManipulator;
use Jegex\Media\MediaCollections\Models\Media;
use Spatie\ImageOptimizer\OptimizerChain;

class PerformConversionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public int $timeout = 120;

    public function __construct(
        public array $conversions,
        public int|string $mediaId
    ) {
        $this->onConnection(config('media.queue_connection_name') ?: null);
        $this->onQueue(config('media.queue_name') ?: null);
    }

    public function handle(): void
    {
        $media = Media::findOrFail($this->mediaId);
        $manipulator = app(FileManipulator::class);

        $sourcePath = $this->getMediaFilePath($media);

        foreach ($this->conversions as $conversionName => $conversionConfig) {
            $targetPath = $this->getConversionPath($media, $conversionName);

            $format = $conversionConfig['format'] ?? pathinfo($media->file_name, PATHINFO_EXTENSION);
            $manipulations = $conversionConfig['manipulations'] ?? [];
            $optimize = $conversionConfig['optimize'] ?? false;

            $generated = $manipulator->createConversion(
                $sourcePath,
                $targetPath,
                $format,
                $manipulations
            );

            if ($generated && $optimize) {
                $this->optimizeImage($targetPath);
            }

            if ($generated) {
                $media->generated_conversions[$conversionName] = true;
                $media->save();
            }
        }
    }

    public static function dispatchUsingConnection(array $conversions, int|string $mediaId): void
    {
        $job = new self($conversions, $mediaId);

        $afterCommit = config('media.queue_conversions_after_database_commit', true);

        if ($afterCommit) {
            dispatch($job)->afterCommit();
        } else {
            dispatch($job);
        }
    }

    protected function getMediaFilePath(Media $media): string
    {
        return Storage::disk($media->disk)->path(
            $media->getPath().'/'.$media->file_name
        );
    }

    protected function getConversionPath(Media $media, string $conversionName): string
    {
        $pathGenerator = config('media.path_generator');
        $basePath = app($pathGenerator)->getPathForConversions($media);

        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);

        return Storage::disk($media->getConversionsDiskName())->path(
            $basePath.'/'.$conversionName.'.'.$extension
        );
    }

    protected function optimizeImage(string $path): void
    {
        $optimizerChain = app(OptimizerChain::class);
        $optimizerChain->optimize($path);
    }
}
