<?php

namespace Jegex\Media\MediaCollections\Models\Observers;

use Illuminate\Support\Facades\Storage;
use Jegex\Media\Conversions\ConversionCollection;
use Jegex\Media\Conversions\FileManipulator;
use Jegex\Media\Conversions\Jobs\PerformConversionsJob;
use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\ResponsiveImages\Jobs\GenerateResponsiveImagesJob;
use Spatie\ImageOptimizer\OptimizerChain;

class MediaObserver
{
    public function creating(Media $media): void
    {
        if (empty($media->order_column)) {
            $media->setHighestOrderNumber();
        }
    }

    public function created(Media $media): void
    {
        $this->dispatchConversionJobs($media);
        $this->dispatchResponsiveImagesJob($media);
    }

    public function updating(Media $media): void
    {
        if (config('media.moves_media_on_update', false) && $media->isDirty('name')) {
            $fileNamer = config('media.file_namer');
            $newFileName = app($fileNamer)->responsiveFileName($media->name);

            if ($media->file_name !== $newFileName.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION)) {
                $this->renameFile($media, $newFileName);
            }
        }
    }

    public function deleting(Media $media): void
    {
        $fileRemover = config('media.file_remover_class');
        app($fileRemover)->removeAllFiles($media);
    }

    protected function dispatchConversionJobs(Media $media): void
    {
        $model = $media->model;

        if ($model && method_exists($model, 'getMediaConversions')) {
            $conversions = $model->getMediaConversions();
        } else {
            $conversions = ConversionCollection::createForMedia($media);
        }

        $queuedConversions = $conversions->getQueuedConversions();
        $nonQueuedConversions = $conversions->getNonQueuedConversions();

        if ($queuedConversions->isNotEmpty()) {
            PerformConversionsJob::dispatchUsingConnection(
                $queuedConversions->toArray(),
                $media->id
            );
        }

        if ($nonQueuedConversions->isNotEmpty()) {
            $this->performNonQueuedConversions($media, $nonQueuedConversions);
        }
    }

    protected function performNonQueuedConversions(Media $media, $conversions): void
    {
        $manipulator = app(FileManipulator::class);
        $sourcePath = Storage::disk($media->disk)->path(
            $media->getPath().'/'.$media->file_name
        );

        foreach ($conversions as $conversion) {
            $conversionName = is_array($conversion) ? ($conversion['name'] ?? 'unknown') : $conversion->getName();
            $conversionConfig = is_array($conversion) ? $conversion : [];

            $targetPath = Storage::disk($media->getConversionsDiskName())->path(
                app(config('media.path_generator'))->getPathForConversions($media).'/'.$conversionName.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION)
            );

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
                $optimizerChain = app(OptimizerChain::class);
                $optimizerChain->optimize($targetPath);
            }

            if ($generated) {
                $media->generated_conversions[$conversionName] = true;
            }
        }

        $media->save();
    }

    protected function dispatchResponsiveImagesJob(Media $media): void
    {
        if (! str_starts_with($media->mime_type ?? '', 'image/')) {
            return;
        }

        GenerateResponsiveImagesJob::dispatchUsingConnection($media->id);
    }

    protected function renameFile(Media $media, string $newFileName): void
    {
        $pathGenerator = app(config('media.path_generator'));
        $oldPath = $media->getPath().'/'.$media->file_name;
        $newPath = $media->getPath().'/'.$newFileName.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION);

        $disk = Storage::disk($media->disk);

        if ($disk->exists($oldPath)) {
            $disk->move($oldPath, $newPath);
            $media->file_name = $newFileName.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION);
        }
    }
}
