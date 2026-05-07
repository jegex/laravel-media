<?php

namespace Jegex\Media\ResponsiveImages\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\ResponsiveImages\TinyPlaceholderGenerator\Blurred;
use Jegex\Media\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator;
use Spatie\Image\Enums\Constraint;
use Spatie\Image\Image;

class GenerateResponsiveImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public int $timeout = 120;

    public function __construct(
        public int|string $mediaId
    ) {
        $this->onConnection(config('media.queue_connection_name') ?: null);
        $this->onQueue(config('media.queue_name') ?: null);
    }

    public function handle(): void
    {
        $media = Media::findOrFail($this->mediaId);

        if (! $this->shouldBeResponsive($media)) {
            return;
        }

        $sourcePath = $this->getMediaFilePath($media);

        if (! file_exists($sourcePath)) {
            return;
        }

        $image = Image::load($sourcePath);

        $width = $image->getWidth();
        $height = $image->getHeight();

        $calculator = new FileSizeOptimizedWidthCalculator;
        $widths = $calculator->calculateWidths(
            filesize($sourcePath),
            $width,
            $height
        );

        $responsiveImages = [];

        foreach ($widths as $targetWidth) {
            $fileName = $this->getResponsiveFileName($media, $targetWidth);
            $targetPath = $this->getResponsiveImagePath($media, $fileName);

            Image::load($sourcePath)
                ->width($targetWidth, [Constraint::PreserveAspectRatio])
                ->save($targetPath);

            $responsiveImages[] = $fileName;
        }

        if (config('media.responsive_images.use_tiny_placeholders', true)) {
            $placeholderGenerator = new Blurred;
            $placeholder = $placeholderGenerator->generateTinyPlaceholder($sourcePath);

            if ($placeholder) {
                $media->responsive_images['tiny_placeholder'] = $placeholder;
            }
        }

        $media->responsive_images['widths'] = $responsiveImages;
        $media->save();
    }

    public static function dispatchUsingConnection(int|string $mediaId): void
    {
        $job = new self($mediaId);

        $afterCommit = config('media.queue_conversions_after_database_commit', true);

        if ($afterCommit) {
            dispatch($job)->afterCommit();
        } else {
            dispatch($job);
        }
    }

    protected function shouldBeResponsive(Media $media): bool
    {
        return str_starts_with($media->mime_type, 'image/');
    }

    protected function getMediaFilePath(Media $media): string
    {
        return Storage::disk($media->disk)->path(
            $media->getPath().'/'.$media->file_name
        );
    }

    protected function getResponsiveImagePath(Media $media, string $fileName): string
    {
        $pathGenerator = config('media.path_generator');
        $basePath = app($pathGenerator)->getPathForResponsiveImages($media);

        return Storage::disk($media->disk)->path(
            $basePath.'/'.$fileName
        );
    }

    protected function getResponsiveFileName(Media $media, int $width): string
    {
        $fileNamer = config('media.file_namer');
        $baseName = app($fileNamer)->responsiveFileName($media->file_name);
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);

        return "{$baseName}-{$width}.{$extension}";
    }
}
