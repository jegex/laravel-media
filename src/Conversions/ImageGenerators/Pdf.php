<?php

namespace Jegex\Media\Conversions\ImageGenerators;

use Imagick;

class Pdf extends ImageGenerator
{
    public function canConvert(string $mimeType): bool
    {
        return $mimeType === 'application/pdf';
    }

    protected function doConvert(string $inputPath, string $outputPath): string
    {
        if (class_exists(Imagick::class)) {
            $imagick = new Imagick;
            $imagick->readImage($inputPath.'[0]');
            $imagick->setImageFormat('jpg');
            $imagick->writeImage($outputPath);
            $imagick->clear();

            return $outputPath;
        }

        throw new \RuntimeException('Imagick is required to convert PDF files.');
    }
}
