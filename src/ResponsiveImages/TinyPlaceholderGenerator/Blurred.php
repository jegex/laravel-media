<?php

namespace Jegex\Media\ResponsiveImages\TinyPlaceholderGenerator;

use Spatie\Image\Image;

class Blurred
{
    public function generateTinyPlaceholder(string $contentImage): string
    {
        try {
            $image = Image::load($contentImage);

            $width = $image->getWidth();
            $height = $image->getHeight();

            $tinyWidth = 32;
            $tinyHeight = (int) round(($tinyWidth / $width) * $height);

            $tempFile = tempnam(sys_get_temp_dir(), 'ml_placeholder_');

            $image
                ->width($tinyWidth)
                ->blur(10)
                ->quality(20)
                ->save($tempFile);

            $base64Content = base64_encode(file_get_contents($tempFile));
            $mimeType = mime_content_type($tempFile);

            @unlink($tempFile);

            return "data:{$mimeType};base64,{$base64Content}";
        } catch (\Exception $e) {
            return '';
        }
    }
}
