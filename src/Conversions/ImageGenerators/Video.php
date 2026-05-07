<?php

namespace Jegex\Media\Conversions\ImageGenerators;

use Symfony\Component\Process\Process;

class Video extends ImageGenerator
{
    public function canConvert(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    protected function doConvert(string $inputPath, string $outputPath): string
    {
        $ffmpegPath = config('media.ffmpeg_path', '/usr/bin/ffmpeg');
        $outputSeconds = 1;

        $process = new Process([
            $ffmpegPath,
            '-i', $inputPath,
            '-vf', "select=eq(n\\,{$outputSeconds})",
            '-vframes', '1',
            $outputPath,
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to extract video frame: '.$process->getErrorOutput());
        }

        return $outputPath;
    }
}
