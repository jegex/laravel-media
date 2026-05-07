<?php

namespace Jegex\Media\Conversions\ImageGenerators;

use Spatie\Image\Image;

class GenericImage extends ImageGenerator
{
    public function canConvert(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
    }

    protected function doConvert(string $inputPath, string $outputPath): string
    {
        Image::load($inputPath)->save($outputPath);

        return $outputPath;
    }
}
