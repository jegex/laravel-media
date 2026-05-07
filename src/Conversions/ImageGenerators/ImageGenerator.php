<?php

namespace Jegex\Media\Conversions\ImageGenerators;

abstract class ImageGenerator
{
    abstract public function canConvert(string $mimeType): bool;

    public function canHandleMedia(string $mimeType): bool
    {
        return $this->canConvert($mimeType);
    }

    public function convert(string $path, ?string $targetExtension = null): string
    {
        if ($targetExtension) {
            $targetPath = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME).'.'.$targetExtension;

            return $this->doConvert($path, $targetPath);
        }

        return $this->doConvert($path, $path);
    }

    abstract protected function doConvert(string $inputPath, string $outputPath): string;
}
