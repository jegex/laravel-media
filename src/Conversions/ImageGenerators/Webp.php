<?php

namespace Jegex\Media\Conversions\ImageGenerators;

use Spatie\Image\Image;

class Webp extends ImageGenerator
{
    public function canConvert(string $mimeType): bool
    {
        return $mimeType === 'image/webp';
    }

    protected function doConvert(string $inputPath, string $outputPath): string
    {
        Image::load($inputPath)
            ->format('webp')
            ->save($outputPath);

        return $outputPath;
    }
}
