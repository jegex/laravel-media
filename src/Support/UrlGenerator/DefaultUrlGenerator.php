<?php

namespace Jegex\Media\Support\UrlGenerator;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Jegex\Media\MediaCollections\Models\Media;

class DefaultUrlGenerator
{
    public function getUrl(Media $media): string
    {
        $disk = Storage::disk($media->getDiskName());

        if (config('media.version_urls')) {
            $url = $disk->url($media->getPath().'/'.$media->file_name);

            return $url.'?v='.$media->updated_at->timestamp;
        }

        return $disk->url($media->getPath().'/'.$media->file_name);
    }

    public function getUrlForConversion(Media $media, string $conversionName): string
    {
        $disk = Storage::disk($media->getConversionsDiskName());
        $pathGenerator = config('media.path_generator');
        $basePath = app($pathGenerator)->getPathForConversions($media);
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);

        if (config('media.version_urls')) {
            $url = $disk->url($basePath.'/'.$conversionName.'.'.$extension);

            return $url.'?v='.$media->updated_at->timestamp;
        }

        return $disk->url($basePath.'/'.$conversionName.'.'.$extension);
    }

    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string
    {
        $disk = Storage::disk($media->getDiskName());

        return $disk->temporaryUrl(
            $media->getPath().'/'.$media->file_name,
            $expiration
        );
    }

    public function getPath(Media $media): string
    {
        return $media->getPath().'/'.$media->file_name;
    }
}
