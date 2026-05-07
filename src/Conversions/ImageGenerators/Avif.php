<?php

namespace Jegex\Media\Conversions\ImageGenerators;

use Spatie\Image\Image;

class Avif extends ImageGenerator
{
    public function canConvert(string $mimeType): bool
    {
        return $mimeType === 'image/avif';
    }

    protected function doConvert(string $inputPath, string $outputPath): string
    {
        Image::load($inputPath)
            ->format('avif')
            ->save($outputPath);

        return $outputPath;
    }
}
