<?php

namespace Jegex\Media\Conversions\ImageGenerators;

use Imagick;

class Svg extends ImageGenerator
{
    public function canConvert(string $mimeType): bool
    {
        return $mimeType === 'image/svg+xml';
    }

    protected function doConvert(string $inputPath, string $outputPath): string
    {
        if (class_exists(Imagick::class)) {
            $imagick = new Imagick;
            $imagick->readImage($inputPath);
            $imagick->setImageFormat('png');
            $imagick->writeImage($outputPath);
            $imagick->clear();

            return $outputPath;
        }

        throw new \RuntimeException('Imagick is required to convert SVG files.');
    }
}
