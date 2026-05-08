<?php

namespace Jegex\Media\Support\PathGenerator;

use Jegex\Media\MediaCollections\Models\Media;

class DefaultPathGenerator
{
    public function getPath(Media $media): string
    {
        return config('media.prefix', '').$this->getBasePath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images';
    }

    protected function getBasePath(Media $media): string
    {
        if (config('media.path_type') === 'id') {
            return (string) ($media->getKey() ?? $media->uuid);
        }

        return $media->uuid;
    }
}
