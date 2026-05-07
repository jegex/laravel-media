<?php

namespace Jegex\Media\Support\FileRemover;

use Illuminate\Support\Facades\Storage;
use Jegex\Media\MediaCollections\Models\Media;

class DefaultFileRemover
{
    public function removeAllFiles(Media $media): void
    {
        $this->removeFromDisk($media->getDiskName(), $media);

        if ($media->getConversionsDiskName() !== $media->getDiskName()) {
            $this->removeFromDisk($media->getConversionsDiskName(), $media);
        }
    }

    public function removeFromDisk(string $diskName, Media $media): void
    {
        $disk = Storage::disk($diskName);
        $path = $media->getPath();

        if ($disk->exists($path)) {
            $disk->deleteDirectory($path);
        }
    }
}
