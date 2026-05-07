<?php

namespace Jegex\Media\Support\FileNamer;

use Jegex\Media\MediaCollections\Models\Media;

class DefaultFileNamer
{
    public function responsiveFileName(string $fileName): string
    {
        return pathinfo($fileName, PATHINFO_FILENAME);
    }

    public function getTemporaryDirectoryPostfix(Media $media): string
    {
        return 'media-'.$media->getKey();
    }
}
