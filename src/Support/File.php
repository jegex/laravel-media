<?php

namespace Jegex\Media\Support;

use Illuminate\Support\Facades\Storage;
use Jegex\Media\MediaCollections\Models\Media;

class File
{
    public function getRemoteFileForMedia(Media $media): string
    {
        return Storage::disk($media->disk)->path($this->getMediaDirectory($media).'/'.$media->file_name);
    }

    public function getMediaDirectory(Media $media): string
    {
        $pathGenerator = config('media.path_generator');

        return app($pathGenerator)->getPath($media);
    }

    public function getMimeType(Media $media): string
    {
        return Storage::disk($media->disk)->mimeType($this->getMediaDirectory($media).'/'.$media->file_name);
    }

    public function getFileSize(Media $media): int
    {
        return Storage::disk($media->disk)->size($this->getMediaDirectory($media).'/'.$media->file_name);
    }

    public function getLastModified(Media $media): int
    {
        return Storage::disk($media->disk)->lastModified($this->getMediaDirectory($media).'/'.$media->file_name);
    }
}
